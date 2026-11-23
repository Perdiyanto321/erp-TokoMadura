<?php

namespace App\Support;

class Rupiah
{
    /**
     * Format nominal menjadi format mata uang Rupiah.
     * Contoh: Rp1.250.000, atau (Rp340.000) jika negatif saat $formatNegatifKurung = true.
     */
    public static function format(float|int|string|null $nominal, bool $formatNegatifKurung = false): string
    {
        $nilai = (float) ($nominal ?? 0);

        if ($nilai < 0 && $formatNegatifKurung) {
            return '(Rp' . number_format(abs($nilai), 0, ',', '.') . ')';
        }

        if ($nilai < 0) {
            return '-Rp' . number_format(abs($nilai), 0, ',', '.') . '';
        }

        return 'Rp' . number_format($nilai, 0, ',', '.');
    }
}
