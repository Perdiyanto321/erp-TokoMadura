<?php

namespace App\Services;

use App\Models\DetailPembelian;
use App\Models\Produk;
use App\Models\Supplier;
use Illuminate\Support\Collection;

class RestokService
{
    /**
     * Dapatkan daftar rekomendasi restok untuk produk yang stoknya <= stok_minimum.
     * Kueri murni tanpa efek samping penulisan data (TS-04).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getRekomendasi(): Collection
    {
        $produkList = Produk::with('kategori')
            ->where('status', 'aktif')
            ->whereColumn('stok', '<=', 'stok_minimum')
            ->orderBy('nama')
            ->get();

        $semuaSupplier = Supplier::orderBy('nama')->get();
        $defaultSupplierPertama = $semuaSupplier->first();

        return $produkList->map(function (Produk $p) use ($semuaSupplier, $defaultSupplierPertama) {
            // Hitung kuantitas yang sedang dipesan (PO disetujui/dikirim/diterima_sebagian yang belum diterima)
            $sedangDipesanBeli = DetailPembelian::where('produk_id', $p->id)
                ->whereHas('pembelian', function ($q) {
                    $q->whereIn('status', ['disetujui', 'dikirim', 'diterima_sebagian']);
                })
                ->with('detailPenerimaan')
                ->get()
                ->sum(function ($dp) {
                    $diterima = $dp->detailPenerimaan->sum('qty_diterima');
                    return max(0, $dp->qty_pesan - $diterima);
                });

            $isi = max(1, $p->isi_per_satuan_beli);
            $sedangDipesanDasar = $sedangDipesanBeli * $isi;

            $saranDasar = max(0, $p->stok_target - $p->stok - $sedangDipesanDasar);
            $saranSatuanBeli = (int) ceil($saranDasar / $isi);

            // Supplier dari PO terakhir produk ini
            $lastPoDetail = DetailPembelian::where('produk_id', $p->id)
                ->with('pembelian.supplier')
                ->latest('id')
                ->first();

            $supplierTerpilih = $lastPoDetail?->pembelian?->supplier ?? $defaultSupplierPertama;

            return [
                'produk_id' => $p->id,
                'kode' => $p->kode,
                'nama' => $p->nama,
                'kategori' => $p->kategori?->nama,
                'stok' => $p->stok,
                'stok_minimum' => $p->stok_minimum,
                'stok_target' => $p->stok_target,
                'satuan' => $p->satuan,
                'satuan_beli' => $p->satuan_beli,
                'isi_per_satuan_beli' => $isi,
                'sedang_dipesan' => $sedangDipesanBeli,
                'saran_dasar' => $saranDasar,
                'saran_satuan_beli' => $saranSatuanBeli,
                'harga_beli' => $p->harga_beli,
                'supplier_id' => $supplierTerpilih?->id,
                'supplier_nama' => $supplierTerpilih?->nama,
            ];
        });
    }
}
