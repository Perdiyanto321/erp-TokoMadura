<?php

namespace App\Services;

use App\Exceptions\AksiTidakDiizinkan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Pengeluaran;
use App\Models\Penjualan;
use App\Models\User;
use App\Support\Rupiah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KeuanganService
{
    /**
     * Hitung sisa utang untuk suatu pembelian (PO) kredit.
     */
    public function hitungSisaUtang(Pembelian $po): float
    {
        $po->loadMissing(['details.detailPenerimaan', 'pembayaran']);

        $nilaiDiterima = 0;
        foreach ($po->details as $detail) {
            $qtyDiterima = (int) $detail->detailPenerimaan->sum('qty_diterima');
            $nilaiDiterima += $qtyDiterima * (float) $detail->harga_beli;
        }

        $totalDibayar = (float) $po->pembayaran->where('arah', 'keluar')->sum('jumlah');

        return max(0, $nilaiDiterima - $totalDibayar);
    }

    /**
     * Bayar utang supplier atas PO kredit (FR-22, BR-07, TS-07).
     */
    public function bayarUtang(
        Pembelian $po,
        float $jumlah,
        string $metode,
        User $user,
        ?string $tanggal = null
    ): Pembayaran {
        return DB::transaction(function () use ($po, $jumlah, $metode, $user, $tanggal) {
            $tgl = $tanggal ?? date('Y-m-d');

            $poTerkunci = Pembelian::where('id', $po->id)
                ->with(['details.detailPenerimaan', 'pembayaran'])
                ->lockForUpdate()
                ->first();

            if ($poTerkunci->jenis_bayar !== 'kredit') {
                throw new AksiTidakDiizinkan("PO ini bukan pembelian kredit.");
            }

            if ($jumlah <= 0) {
                throw new AksiTidakDiizinkan("Jumlah pembayaran harus lebih besar dari 0.");
            }

            // Hitung nilai diterima dan total dibayar
            $nilaiDiterima = 0;
            foreach ($poTerkunci->details as $detail) {
                $qtyDiterima = (int) $detail->detailPenerimaan->sum('qty_diterima');
                $nilaiDiterima += $qtyDiterima * (float) $detail->harga_beli;
            }

            $totalDibayar = (float) $poTerkunci->pembayaran->where('arah', 'keluar')->sum('jumlah');
            $sisaUtang = max(0, $nilaiDiterima - $totalDibayar);

            if ($sisaUtang <= 0) {
                throw new AksiTidakDiizinkan("Utang untuk PO ini sudah lunas.");
            }

            if ($jumlah > $sisaUtang) {
                $jumlahRp = Rupiah::format($jumlah);
                $sisaRp = Rupiah::format($sisaUtang);
                throw new AksiTidakDiizinkan("Pembayaran {$jumlahRp} melebihi sisa utang {$sisaRp}.");
            }

            $pembayaran = Pembayaran::create([
                'penjualan_id' => null,
                'pembelian_id' => $poTerkunci->id,
                'user_id' => $user->id,
                'arah' => 'keluar',
                'jumlah' => $jumlah,
                'tanggal' => $tgl,
                'metode' => $metode,
            ]);

            LogAktivitasService::catat(
                'utang.bayar',
                'Pembayaran',
                $pembayaran->id,
                [
                    'nomor_po' => $poTerkunci->nomor_po,
                    'jumlah' => $jumlah,
                    'metode' => $metode,
                ],
                $user
            );

            return $pembayaran;
        });
    }

    /**
     * Dapatkan daftar utang supplier (PO kredit).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getDaftarUtang(): Collection
    {
        $poList = Pembelian::where('jenis_bayar', 'kredit')
            ->whereNotIn('status', ['draf', 'dibatalkan'])
            ->with(['supplier', 'details.detailPenerimaan', 'pembayaran'])
            ->orderBy('jatuh_tempo')
            ->get();

        return $poList->map(function (Pembelian $po) {
            $nilaiDiterima = 0;
            foreach ($po->details as $d) {
                $qty = (int) $d->detailPenerimaan->sum('qty_diterima');
                $nilaiDiterima += $qty * (float) $d->harga_beli;
            }

            $totalDibayar = (float) $po->pembayaran->where('arah', 'keluar')->sum('jumlah');
            $sisa = max(0, $nilaiDiterima - $totalDibayar);

            $statusUtang = 'Belum lunas';
            if ($sisa == 0 && $nilaiDiterima > 0) {
                $statusUtang = 'Lunas';
            } elseif ($totalDibayar > 0 && $sisa > 0) {
                $statusUtang = 'Sebagian';
            }

            return [
                'id' => $po->id,
                'nomor_po' => $po->nomor_po,
                'supplier_nama' => $po->supplier?->nama,
                'tanggal' => $po->tanggal,
                'jatuh_tempo' => $po->jatuh_tempo,
                'nilai_diterima' => $nilaiDiterima,
                'total_dibayar' => $totalDibayar,
                'sisa_utang' => $sisa,
                'status_utang' => $statusUtang,
            ];
        });
    }

    /**
     * Hitung total utang supplier yang belum lunas.
     */
    public function hitungTotalUtang(): float
    {
        return $this->getDaftarUtang()->sum('sisa_utang');
    }

    /**
     * Hitung total piutang kasbon pelanggan yang belum lunas.
     */
    public function hitungTotalPiutang(): float
    {
        $penjualanKasbon = Penjualan::where('jenis_bayar', 'kasbon')
            ->where('status', 'selesai')
            ->with('pembayaran')
            ->get();

        return (float) $penjualanKasbon->sum(function (Penjualan $p) {
            $dibayar = $p->pembayaran->where('arah', 'masuk')->sum('jumlah');
            return max(0, (float) $p->total - $dibayar);
        });
    }

    /**
     * Rekap kas masuk, kas keluar, dan saldo kas.
     *
     * @return array{saldo_awal: float, kas_masuk: float, kas_keluar: float, saldo_akhir: float}
     */
    public function rekapKas(?string $dari = null, ?string $sampai = null): array
    {
        $saldoAwalConfig = (float) config('keuangan.saldo_awal', 0);

        $queryMasuk = Pembayaran::where('arah', 'masuk');
        $queryKeluarBayar = Pembayaran::where('arah', 'keluar');
        $queryBiaya = Pengeluaran::query();

        if ($dari) {
            $queryMasuk->where('tanggal', '>=', $dari);
            $queryKeluarBayar->where('tanggal', '>=', $dari);
            $queryBiaya->where('tanggal', '>=', $dari);
        }

        if ($sampai) {
            $queryMasuk->where('tanggal', '<=', $sampai);
            $queryKeluarBayar->where('tanggal', '<=', $sampai);
            $queryBiaya->where('tanggal', '<=', $sampai);
        }

        $kasMasuk = (float) $queryMasuk->sum('jumlah');
        $kasKeluar = (float) $queryKeluarBayar->sum('jumlah') + (float) $queryBiaya->sum('nominal');

        return [
            'saldo_awal' => $saldoAwalConfig,
            'kas_masuk' => $kasMasuk,
            'kas_keluar' => $kasKeluar,
            'saldo_akhir' => $saldoAwalConfig + $kasMasuk - $kasKeluar,
        ];
    }
}
