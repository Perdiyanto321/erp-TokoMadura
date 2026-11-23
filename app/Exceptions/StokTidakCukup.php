<?php

namespace App\Exceptions;

use App\Models\Produk;
use Exception;

class StokTidakCukup extends Exception
{
    public function __construct(public ?Produk $produk = null, public int $diminta = 0, public int $tersedia = 0)
    {
        $nama = $produk?->nama ?? 'Produk';
        $tersediaStok = $produk ? $produk->stok : $tersedia;
        parent::__construct("Stok {$nama} tinggal {$tersediaStok}. Kurangi jumlah atau hapus item.");
    }
}
