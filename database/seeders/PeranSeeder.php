<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class PeranSeeder extends Seeder
{
    /**
     * Seed master data peran (roles).
     */
    public function run(): void
    {
        $roles = ['admin', 'kasir', 'gudang', 'keuangan'];

        foreach ($roles as $r) {
            Role::firstOrCreate(['nama' => $r]);
        }
    }
}
