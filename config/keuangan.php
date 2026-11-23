<?php

return [
    'saldo_awal' => env('SALDO_AWAL_KAS', 5000000.00),
    'kategori_biaya' => [
        'listrik' => 'Listrik & Air',
        'sewa' => 'Sewa Tempat',
        'gaji' => 'Gaji Karyawan',
        'transport' => 'Transportasi & Logistik',
        'kemasan' => 'Kantong & Kemasan',
        'lain-lain' => 'Lain-lain',
    ],
];
