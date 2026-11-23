<?php

namespace App\Exceptions;

use App\Models\Pelanggan;
use App\Support\Rupiah;
use Exception;

class BatasKasbonTerlampaui extends Exception
{
    public function __construct(public ?Pelanggan $pelanggan = null, public float $akanMenjadi = 0, public float $batas = 0)
    {
        $nama = $pelanggan?->nama ?? 'Pelanggan';
        $akanRp = Rupiah::format($akanMenjadi);
        $batasRp = Rupiah::format($batas);
        parent::__construct("Kasbon {$nama} akan menjadi {$akanRp}, melebihi batas {$batasRp}.");
    }
}
