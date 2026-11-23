<?php

namespace App\Exceptions;

use Exception;

class AksiTidakDiizinkan extends Exception
{
    public function __construct(string $pesan = "Aksi tidak diizinkan.")
    {
        parent::__construct($pesan);
    }
}
