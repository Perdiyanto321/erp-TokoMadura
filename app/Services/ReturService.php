<?php

namespace App\Services;

use App\Exceptions\AksiTidakDiizinkan;
use App\Models\DetailPenjualan;
use App\Models\DetailRetur;
use App\Models\Pembayaran;
use App\Models\Penjualan;
use App\Models\ReturPenjualan;
use App\Models\User;
use App\Support\NomorDokumen;
use Illuminate\Support\Facades\DB;

class ReturService
{
    public function __construct(
        protected InventoriService $inventoriService
    ) {}

    /**
     * Ajukan retur penjualan oleh Kasir (status: menunggu).
     *
     * @param Penjualan $penjualan
     * @param string $alasan
     * @param array<int, array{detail_penjualan_id: int, qty: int, kondisi: string}> $items
     * @param User $kasir
     * @return ReturPenjualan
     */
    public function ajukan(
        Penjualan $penjualan,
        string $alasan,
        array $items,
        User $kasir
    ): ReturPenjualan {
        return DB::transaction(function () use ($penjualan, $alasan, $items, $kasir) {
            $batasHari = (int) config('toko.batas_retur_hari', 7);
            $selisihHari = now()->diffInDays($penjualan->tanggal, false);

            if (abs($selisihHari) > $batasHari) {
                throw new AksiTidakDiizinkan("Retur ditolak. Batas waktu retur adalah {$batasHari} hari setelah pembelian.");
            }

            if ($penjualan->status !== 'selesai') {
                throw new AksiTidakDiizinkan("Hanya transaksi penjualan berstatus selesai yang dapat diretur.");
            }

            $retur = ReturPenjualan::create([
                'penjualan_id' => $penjualan->id,
                'diajukan_oleh' => $kasir->id,
                'disetujui_oleh' => null,
                'nomor' => 'RET-TEMP-' . uniqid(),
                'tanggal' => date('Y-m-d'),
                'alasan' => $alasan,
                'status' => 'menunggu',
                'nilai_refund' => 0,
            ]);

            $nomorRET = NomorDokumen::generate('RET', $retur->id, date('Y-m-d'));
            $retur->update(['nomor' => $nomorRET]);

            $totalRefund = 0;
            foreach ($items as $item) {
                $detail = DetailPenjualan::findOrFail($item['detail_penjualan_id']);
                $qtyRetur = (int) $item['qty'];
                $kondisi = $item['kondisi']; // layak_jual, rusak

                // Hitung sisa retur yang sudah pernah disetujui / diajukan
                $sudahRetur = DetailRetur::where('detail_penjualan_id', $detail->id)
                    ->whereHas('retur', fn ($q) => $q->whereIn('status', ['menunggu', 'disetujui']))
                    ->sum('qty');

                $sisaRetur = $detail->qty - $sudahRetur;
                if ($qtyRetur > $sisaRetur) {
                    throw new AksiTidakDiizinkan("Kuantitas retur ({$qtyRetur}) melebihi sisa item yang dapat diretur ({$sisaRetur}).");
                }

                DetailRetur::create([
                    'retur_id' => $retur->id,
                    'detail_penjualan_id' => $detail->id,
                    'qty' => $qtyRetur,
                    'kondisi' => $kondisi,
                ]);

                $totalRefund += $qtyRetur * (float) $detail->harga_jual;
            }

            $retur->update(['nilai_refund' => $totalRefund]);

            LogAktivitasService::catat(
                'retur.ajukan',
                'ReturPenjualan',
                $retur->id,
                ['nomor' => $nomorRET, 'total_refund' => $totalRefund],
                $kasir
            );

            return $retur;
        });
    }

    /**
     * Setujui pengajuan retur oleh Admin.
     */
    public function setujui(ReturPenjualan $retur, User $admin): ReturPenjualan
    {
        return DB::transaction(function () use ($retur, $admin) {
            $returTerkunci = ReturPenjualan::where('id', $retur->id)
                ->with(['details.detailPenjualan.produk', 'penjualan'])
                ->lockForUpdate()
                ->first();

            if ($returTerkunci->status !== 'menunggu') {
                throw new AksiTidakDiizinkan("Hanya retur berstatus 'menunggu' yang dapat disetujui.");
            }

            foreach ($returTerkunci->details as $dRetur) {
                // Hanya barang layak_jual yang menambah kembali stok persediaan (D-10)
                if ($dRetur->kondisi === 'layak_jual') {
                    $this->inventoriService->ubahStok(
                        $dRetur->detailPenjualan->produk,
                        $dRetur->qty,
                        'retur',
                        $returTerkunci,
                        $admin
                    );
                }
            }

            // Pengembalian dana (refund)
            $penjualan = $returTerkunci->penjualan;
            if ($penjualan->jenis_bayar !== 'kasbon') {
                Pembayaran::create([
                    'penjualan_id' => $penjualan->id,
                    'pembelian_id' => null,
                    'user_id' => $admin->id,
                    'arah' => 'keluar',
                    'jumlah' => $returTerkunci->nilai_refund,
                    'tanggal' => date('Y-m-d'),
                    'metode' => $penjualan->jenis_bayar,
                ]);
            }

            $returTerkunci->update([
                'status' => 'disetujui',
                'disetujui_oleh' => $admin->id,
            ]);

            LogAktivitasService::catat(
                'retur.setujui',
                'ReturPenjualan',
                $returTerkunci->id,
                ['nomor' => $returTerkunci->nomor, 'nilai_refund' => $returTerkunci->nilai_refund],
                $admin
            );

            return $returTerkunci;
        });
    }
}
