<?php

namespace App\Support;

class NomorDokumen
{
    /**
     * Pola nomor dokumen terstandar: {AWALAN}-{TANGGAL}-{ID 6 DIGIT}
     * Contoh: TRX-20261119-000123
     */
    public static function generate(string $awalan, int $id, ?string $tanggal = null): string
    {
        $tglStr = $tanggal ? date('Ymd', strtotime($tanggal)) : date('Ymd');
        $idStr = str_pad((string) $id, 6, '0', STR_PAD_LEFT);

        return "{$awalan}-{$tglStr}-{$idStr}";
    }
}
