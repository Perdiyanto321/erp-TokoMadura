<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Karyawan;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Buat master data Role sesuai proposal (Admin, Kasir, Gudang, Keuangan)
        $roles = [
            'Admin',
            'Kasir',
            'Gudang',
            'Keuangan',
        ];

        foreach ($roles as $namaRole) {
            Role::firstOrCreate(['nama' => $namaRole]);
        }

        // 2. Buat data Karyawan awal untuk Pemilik / Admin
        $roleAdmin = Role::where('nama', 'Admin')->first();

        $karyawanAdmin = Karyawan::firstOrCreate(
            ['nama' => 'Perdiyanto'],
            [
                'jabatan' => 'Pemilik Toko',
                'status'  => 'aktif',
            ]
        );

        // 3. Buat akun User Admin awal
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'role_id'     => $roleAdmin->id,
                'karyawan_id' => $karyawanAdmin->id,
                'password'    => Hash::make('admin123'),
                'status'      => 'aktif',
            ]
        );
    }
}