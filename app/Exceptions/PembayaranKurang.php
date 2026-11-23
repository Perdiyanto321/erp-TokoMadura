<?php

namespace App\Exceptions;

use App\Support\Rupiah;
use Exception;

class PembayaranKurang extends Exception
{
    public function __construct(public float $kurang = 0)
    {
        $kurangRp = Rupiah::format($kurang);
        parent::__construct("Uang yang diterima kurang {$kurangRp} dari total.");
    }
}
