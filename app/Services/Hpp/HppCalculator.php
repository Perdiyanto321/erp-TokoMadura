<?php

namespace App\Services\Hpp;

class HppCalculator
{
    /**
     * Hitung HPP rata-rata bergerak per satuan dasar (pcs).
     *
     * @param int $stokLama Stok sebelum penerimaan (dalam satuan dasar)
     * @param float $hppLama HPP rata-rata sebelum penerimaan
     * @param int $qtyBeli Kuantitas barang masuk dalam satuan beli (mis. dus)
     * @param float $hargaBeli Harga beli per satuan beli (mis. per dus)
     * @param int $isiPerSatuanBeli Rasio konversi satuan dasar per satuan beli (mis. 40 pcs/dus)
     * @return float HPP rata-rata bergerak baru (dibulatkan 2 desimal)
     */
    public static function hitung(
        int $stokLama,
        float $hppLama,
        int $qtyBeli,
        float $hargaBeli,
        int $isiPerSatuanBeli = 1
    ): float {
        $isi = max(1, $isiPerSatuanBeli);
        $qtyDasar = $qtyBeli * $isi;
        $hargaDasar = $hargaBeli / $isi;

        $totalStok = $stokLama + $qtyDasar;

        if ($totalStok <= 0) {
            return round($hargaDasar, 2);
        }

        if ($stokLama <= 0) {
            return round($hargaDasar, 2);
        }

        $totalNilai = ($stokLama * $hppLama) + ($qtyDasar * $hargaDasar);
        $hppBaru = $totalNilai / $totalStok;

        return round($hppBaru, 2);
    }
}
