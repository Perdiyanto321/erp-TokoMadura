<?php

namespace App\Services;

use App\Exceptions\AksiTidakDiizinkan;
use App\Models\DetailPembelian;
use App\Models\DetailPenerimaan;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\PenerimaanBarang;
use App\Models\Produk;
use App\Models\User;
use App\Services\Hpp\HppCalculator;
use App\Support\NomorDokumen;
use Illuminate\Support\Facades\DB;

class PembelianService
{
    public function __construct(
        protected InventoriService $inventoriService
    ) {}

    /**
     * Buat draf Purchase Order (PO).
     *
     * @param array{
     *     supplier_id: int,
     *     jenis_bayar: string,
     *     tanggal?: string|null,
     *     jatuh_tempo?: string|null,
     *     items: array<int, array{produk_id: int, qty_pesan: int, harga_beli?: float|null}>
     * } $data
     * @param User $pembuat
     * @return Pembelian
     */
    public function buatDraf(array $data, User $pembuat): Pembelian
    {
        return DB::transaction(function () use ($data, $pembuat) {
            $tanggal = $data['tanggal'] ?? date('Y-m-d');
            $jenisBayar = strtolower($data['jenis_bayar']);
            $jatuhTempo = ($jenisBayar === 'kredit') ? ($data['jatuh_tempo'] ?? null) : null;

            if ($jenisBayar === 'kredit' && !$jatuhTempo) {
                throw new AksiTidakDiizinkan("Pembelian kredit wajib menyertakan tanggal jatuh tempo.");
            }

            if (empty($data['items'])) {
                throw new AksiTidakDiizinkan("PO harus memiliki minimal satu produk.");
            }

            $po = Pembelian::create([
                'supplier_id' => $data['supplier_id'],
                'dibuat_oleh' => $pembuat->id,
                'disetujui_oleh' => null,
                'nomor_po' => 'DRAFT-' . uniqid(),
                'tanggal' => $tanggal,
                'status' => 'draf',
                'jenis_bayar' => $jenisBayar,
                'jatuh_tempo' => $jatuhTempo,
                'total' => 0,
            ]);

            $nomorPO = NomorDokumen::generate('PO', $po->id, $tanggal);
            $po->update(['nomor_po' => $nomorPO]);

            $total = 0;
            foreach ($data['items'] as $item) {
                $produk = Produk::findOrFail($item['produk_id']);
                $qtyPesan = (int) $item['qty_pesan'];
                $hargaBeli = isset($item['harga_beli']) && $item['harga_beli'] > 0
                    ? (float) $item['harga_beli']
                    : (float) $produk->harga_beli;

                if ($qtyPesan <= 0) {
                    throw new AksiTidakDiizinkan("Jumlah pesan harus lebih besar dari 0.");
                }

                DetailPembelian::create([
                    'pembelian_id' => $po->id,
                    'produk_id' => $produk->id,
                    'qty_pesan' => $qtyPesan,
                    'harga_beli' => $hargaBeli,
                ]);

                $total += $qtyPesan * $hargaBeli;
            }

            $po->update(['total' => $total]);

            LogAktivitasService::catat(
                'po.buat',
                'Pembelian',
                $po->id,
                ['nomor_po' => $nomorPO, 'total' => $total],
                $pembuat
            );

            return $po;
        });
    }

    /**
     * Setujui PO (Hanya Admin).
     */
    public function setujui(Pembelian $po, User $admin): Pembelian
    {
        return DB::transaction(function () use ($po, $admin) {
            $poTerkunci = Pembelian::where('id', $po->id)->lockForUpdate()->first();

            if ($poTerkunci->status !== 'draf') {
                throw new AksiTidakDiizinkan("Hanya PO berstatus Draf yang dapat disetujui.");
            }

            $poTerkunci->update([
                'status' => 'disetujui',
                'disetujui_oleh' => $admin->id,
            ]);

            LogAktivitasService::catat(
                'po.setujui',
                'Pembelian',
                $poTerkunci->id,
                ['nomor_po' => $poTerkunci->nomor_po],
                $admin
            );

            return $poTerkunci;
        });
    }

    /**
     * Tandai PO dikirim ke supplier.
     */
    public function tandaiDikirim(Pembelian $po, User $user): Pembelian
    {
        return DB::transaction(function () use ($po, $user) {
            $poTerkunci = Pembelian::where('id', $po->id)->lockForUpdate()->first();

            if (!in_array($poTerkunci->status, ['disetujui'])) {
                throw new AksiTidakDiizinkan("Hanya PO yang sudah disetujui yang dapat ditandai dikirim.");
            }

            $poTerkunci->update(['status' => 'dikirim']);

            LogAktivitasService::catat(
                'po.dikirim',
                'Pembelian',
                $poTerkunci->id,
                ['nomor_po' => $poTerkunci->nomor_po],
                $user
            );

            return $poTerkunci;
        });
    }

    /**
     * Terima barang dari PO secara bertahap atau penuh.
     *
     * @param Pembelian $po
     * @param array<int, int> $itemsDiterima Key: detail_pembelian_id, Value: qty_diterima (satuan beli)
     * @param User $gudang
     * @param string|null $tanggal
     * @return PenerimaanBarang
     */
    public function terima(
        Pembelian $po,
        array $itemsDiterima,
        User $gudang,
        ?string $tanggal = null
    ): PenerimaanBarang {
        return DB::transaction(function () use ($po, $itemsDiterima, $gudang, $tanggal) {
            $tgl = $tanggal ?? date('Y-m-d');

            $poTerkunci = Pembelian::where('id', $po->id)
                ->with(['details.produk', 'details.detailPenerimaan'])
                ->lockForUpdate()
                ->first();

            if (!in_array($poTerkunci->status, ['disetujui', 'dikirim', 'diterima_sebagian'])) {
                throw new AksiTidakDiizinkan("PO ini masih berstatus '{$poTerkunci->status}'. Barang hanya bisa diterima setelah PO disetujui/dikirim.");
            }

            // Buat penerimaan barang
            $penerimaan = PenerimaanBarang::create([
                'pembelian_id' => $poTerkunci->id,
                'diterima_oleh' => $gudang->id,
                'nomor' => 'TEMP-' . uniqid(),
                'tanggal' => $tgl,
            ]);

            $nomorTRM = NomorDokumen::generate('TRM', $penerimaan->id, $tgl);
            $penerimaan->update(['nomor' => $nomorTRM]);

            $nilaiPenerimaanIni = 0;
            $adaItemDiterima = false;

            foreach ($poTerkunci->details as $detail) {
                $qtyInput = (int) ($itemsDiterima[$detail->id] ?? 0);
                if ($qtyInput <= 0) {
                    continue;
                }

                $sudahDiterima = (int) $detail->detailPenerimaan->sum('qty_diterima');
                $sisa = $detail->qty_pesan - $sudahDiterima;

                if ($qtyInput > $sisa) {
                    $satuanBeli = $detail->produk->satuan_beli ?? 'satuan';
                    throw new AksiTidakDiizinkan("Jumlah diterima {$qtyInput} {$satuanBeli} melebihi sisa pesanan ({$sisa} {$satuanBeli}).");
                }

                $adaItemDiterima = true;

                // Simpan detail penerimaan
                DetailPenerimaan::create([
                    'penerimaan_id' => $penerimaan->id,
                    'detail_pembelian_id' => $detail->id,
                    'qty_diterima' => $qtyInput,
                ]);

                $produk = Produk::where('id', $detail->produk_id)->lockForUpdate()->first();
                $isi = max(1, $produk->isi_per_satuan_beli);
                $qtyDasar = $qtyInput * $isi;

                // Hitung HPP bergerak baru
                $hppBaru = HppCalculator::hitung(
                    $produk->stok,
                    (float) $produk->hpp_rata_rata,
                    $qtyInput,
                    (float) $detail->harga_beli,
                    $isi
                );

                $produk->update(['hpp_rata_rata' => $hppBaru]);

                // Tambah stok fisik via InventoriService
                $this->inventoriService->ubahStok(
                    $produk,
                    $qtyDasar,
                    'penerimaan',
                    $penerimaan,
                    $gudang
                );

                $nilaiPenerimaanIni += $qtyInput * (float) $detail->harga_beli;
            }

            if (!$adaItemDiterima) {
                throw new AksiTidakDiizinkan("Tidak ada barang yang diterima (kuantitas 0).");
            }

            // Periksa sisa seluruh item untuk status PO
            $poTerkunci->load('details.detailPenerimaan');
            $seluruhSelesai = true;
            foreach ($poTerkunci->details as $d) {
                $totalDiterima = (int) $d->detailPenerimaan->sum('qty_diterima');
                if ($totalDiterima < $d->qty_pesan) {
                    $seluruhSelesai = false;
                    break;
                }
            }

            $poTerkunci->update([
                'status' => $seluruhSelesai ? 'selesai' : 'diterima_sebagian',
            ]);

            // Jika pembelian tunai, langsung catat pembayaran keluar senilai penerimaan
            if ($poTerkunci->jenis_bayar === 'tunai' && $nilaiPenerimaanIni > 0) {
                Pembayaran::create([
                    'penjualan_id' => null,
                    'pembelian_id' => $poTerkunci->id,
                    'user_id' => $gudang->id,
                    'arah' => 'keluar',
                    'jumlah' => $nilaiPenerimaanIni,
                    'tanggal' => $tgl,
                    'metode' => 'tunai',
                ]);
            }

            LogAktivitasService::catat(
                'po.terima',
                'PenerimaanBarang',
                $penerimaan->id,
                [
                    'nomor_penerimaan' => $nomorTRM,
                    'nomor_po' => $poTerkunci->nomor_po,
                    'nilai' => $nilaiPenerimaanIni,
                    'status_po' => $poTerkunci->status,
                ],
                $gudang
            );

            return $penerimaan;
        });
    }

    /**
     * Batalkan PO jika belum ada penerimaan barang.
     */
    public function batalkan(Pembelian $po, string $alasan, User $admin): Pembelian
    {
        return DB::transaction(function () use ($po, $alasan, $admin) {
            $poTerkunci = Pembelian::where('id', $po->id)
                ->with('penerimaan')
                ->lockForUpdate()
                ->first();

            if ($poTerkunci->penerimaan->isNotEmpty()) {
                throw new AksiTidakDiizinkan("PO yang sudah memiliki riwayat penerimaan tidak dapat dibatalkan.");
            }

            if ($poTerkunci->status === 'dibatalkan') {
                return $poTerkunci;
            }

            $poTerkunci->update(['status' => 'dibatalkan']);

            LogAktivitasService::catat(
                'po.batal',
                'Pembelian',
                $poTerkunci->id,
                ['nomor_po' => $poTerkunci->nomor_po, 'alasan' => $alasan],
                $admin
            );

            return $poTerkunci;
        });
    }
}
