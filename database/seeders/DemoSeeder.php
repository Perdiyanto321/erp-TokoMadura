<?php

namespace Database\Seeders;

use App\Models\Karyawan;
use App\Models\Kategori;
use App\Models\Pelanggan;
use App\Models\Pengeluaran;
use App\Models\PergerakanStok;
use App\Models\Produk;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoriService;
use App\Services\PembelianService;
use App\Services\PenjualanService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PeranSeeder::class);

        $roleAdmin = Role::where('nama', 'admin')->first();
        $roleKasir = Role::where('nama', 'kasir')->first();
        $roleGudang = Role::where('nama', 'gudang')->first();
        $roleKeuangan = Role::where('nama', 'keuangan')->first();

        // 1. Karyawan & Akun Pengguna
        $usersData = [
            ['nama' => 'Ahmad Fauzi', 'jabatan' => 'Pemilik Toko', 'username' => 'admin', 'role' => $roleAdmin],
            ['nama' => 'Rina Rahmawati', 'jabatan' => 'Kasir Utama', 'username' => 'kasir1', 'role' => $roleKasir],
            ['nama' => 'Dewi Anggraini', 'jabatan' => 'Kasir Shift 2', 'username' => 'kasir2', 'role' => $roleKasir],
            ['nama' => 'Budi Santoso', 'jabatan' => 'Staf Gudang', 'username' => 'gudang', 'role' => $roleGudang],
            ['nama' => 'Siti Maryam', 'jabatan' => 'Staf Keuangan', 'username' => 'keuangan', 'role' => $roleKeuangan],
        ];

        $users = [];
        foreach ($usersData as $ud) {
            $karyawan = Karyawan::firstOrCreate(
                ['nama' => $ud['nama']],
                ['jabatan' => $ud['jabatan'], 'status' => 'aktif']
            );

            $user = User::firstOrCreate(
                ['username' => $ud['username']],
                [
                    'role_id' => $ud['role']->id,
                    'karyawan_id' => $karyawan->id,
                    'password' => Hash::make($ud['username'] === 'admin' ? 'admin123' : 'password'),
                    'status' => 'aktif',
                ]
            );
            $user->password = Hash::make($ud['username'] === 'admin' ? 'admin123' : 'password');
            $user->status = 'aktif';
            $user->save();
            $users[$ud['username']] = $user;
        }

        // 2. Kategori
        $kategoriNames = [
            'Sembako',
            'Minuman',
            'Makanan Ringan',
            'Rokok & Tembakau',
            'Kebutuhan Rumah',
            'Lain-lain',
        ];

        $kategoris = [];
        foreach ($kategoriNames as $kn) {
            $kategoris[$kn] = Kategori::firstOrCreate(['nama' => $kn]);
        }

        // 3. Pelanggan
        $pelangganList = [
            ['nama' => 'Umum (Tunai)', 'no_hp' => null, 'batas_kasbon' => 0],
            ['nama' => 'Pak RT Slamet', 'no_hp' => '0812-3344-5566', 'batas_kasbon' => 500000],
            ['nama' => 'Bu Haji Maryam', 'no_hp' => '0813-7788-9900', 'batas_kasbon' => 1000000],
            ['nama' => 'Cak Mat Warung', 'no_hp' => '0856-1122-3344', 'batas_kasbon' => 300000],
        ];

        $pelanggans = [];
        foreach ($pelangganList as $pl) {
            $pelanggans[] = Pelanggan::firstOrCreate(['nama' => $pl['nama']], $pl);
        }

        // 4. Supplier
        $supplierList = [
            ['nama' => 'PT Madura Sembako Mandiri', 'kontak' => '0811-2233-4455', 'alamat' => 'Jl. Trunojoyo No. 45, Bangkalan'],
            ['nama' => 'CV Barokah Distribusi Retail', 'kontak' => '0812-9988-7766', 'alamat' => 'Kawasan Pergudangan Margomulyo, Surabaya'],
            ['nama' => 'UD Pangan Sejahtera Madura', 'kontak' => '0813-5566-7788', 'alamat' => 'Jl. Raya Kamal No. 88, Kamal, Bangkalan'],
        ];

        $suppliers = [];
        foreach ($supplierList as $sl) {
            $suppliers[] = Supplier::firstOrCreate(['nama' => $sl['nama']], $sl);
        }

        // 5. Produk (±40 produk realistis Toko Madura, 4 produk awal di bawah stok minimum untuk demo)
        $produkMaster = [
            // Sembako (4 produk pertama di bawah minimum untuk demo skenario Bagian 1)
            [
                'kategori' => 'Sembako',
                'kode' => 'P-001',
                'nama' => 'Beras Ramos 5 kg',
                'satuan' => 'karung',
                'satuan_beli' => 'karung',
                'isi_per_satuan_beli' => 1,
                'harga_beli' => 64000,
                'harga_jual' => 70000,
                'hpp_rata_rata' => 64000,
                'stok' => 3, // Di bawah min 5
                'stok_minimum' => 5,
                'stok_target' => 15,
            ],
            [
                'kategori' => 'Sembako',
                'kode' => 'P-002',
                'nama' => 'Minyak Goreng Sawit 1 L',
                'satuan' => 'botol',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 192000, // Rp16.000/botol
                'harga_jual' => 18000,
                'hpp_rata_rata' => 16000,
                'stok' => 4, // Di bawah min 6
                'stok_minimum' => 6,
                'stok_target' => 24,
            ],
            [
                'kategori' => 'Minuman',
                'kode' => 'P-003',
                'nama' => 'Kopi Sachet Kental Manis',
                'satuan' => 'sachet',
                'satuan_beli' => 'renceng',
                'isi_per_satuan_beli' => 10,
                'harga_beli' => 13000, // Rp1.300/sachet
                'harga_jual' => 2000,
                'hpp_rata_rata' => 1300,
                'stok' => 10, // Di bawah min 20
                'stok_minimum' => 20,
                'stok_target' => 60,
            ],
            [
                'kategori' => 'Sembako',
                'kode' => 'P-004',
                'nama' => 'Gula Pasir Kristal 1 kg',
                'satuan' => 'bungkus',
                'satuan_beli' => 'karung',
                'isi_per_satuan_beli' => 20,
                'harga_beli' => 300000, // Rp15.000/kg
                'harga_jual' => 17500,
                'hpp_rata_rata' => 15000,
                'stok' => 2, // Di bawah min 5
                'stok_minimum' => 5,
                'stok_target' => 20,
            ],
            // Produk stok aman
            [
                'kategori' => 'Makanan Ringan',
                'kode' => 'P-005',
                'nama' => 'Mie Instan Goreng Spesial',
                'satuan' => 'bungkus',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 40,
                'harga_beli' => 120000, // Rp3.000/bks
                'harga_jual' => 3500,
                'hpp_rata_rata' => 3000,
                'stok' => 80,
                'stok_minimum' => 20,
                'stok_target' => 120,
            ],
            [
                'kategori' => 'Makanan Ringan',
                'kode' => 'P-006',
                'nama' => 'Mie Instan Kuah Kari Ayam',
                'satuan' => 'bungkus',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 40,
                'harga_beli' => 116000, // Rp2.900/bks
                'harga_jual' => 3500,
                'hpp_rata_rata' => 2900,
                'stok' => 60,
                'stok_minimum' => 20,
                'stok_target' => 100,
            ],
            [
                'kategori' => 'Minuman',
                'kode' => 'P-007',
                'nama' => 'Teh Celup Melati Kotak 25s',
                'satuan' => 'kotak',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 24,
                'harga_beli' => 144000, // Rp6.000/ktk
                'harga_jual' => 7500,
                'hpp_rata_rata' => 6000,
                'stok' => 20,
                'stok_minimum' => 8,
                'stok_target' => 30,
            ],
            [
                'kategori' => 'Kebutuhan Rumah',
                'kode' => 'P-008',
                'nama' => 'Sabun Mandi Batang 80g',
                'satuan' => 'batang',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 36,
                'harga_beli' => 108000, // Rp3.000/btg
                'harga_jual' => 4000,
                'hpp_rata_rata' => 3000,
                'stok' => 30,
                'stok_minimum' => 12,
                'stok_target' => 45,
            ],
            [
                'kategori' => 'Kebutuhan Rumah',
                'kode' => 'P-009',
                'nama' => 'Pasta Gigi Herbal 120g',
                'satuan' => 'kotak',
                'satuan_beli' => 'lusin',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 120000, // Rp10.000/ktk
                'harga_jual' => 13500,
                'hpp_rata_rata' => 10000,
                'stok' => 16,
                'stok_minimum' => 6,
                'stok_target' => 24,
            ],
            [
                'kategori' => 'Kebutuhan Rumah',
                'kode' => 'P-010',
                'nama' => 'Shampo Anti Ketombe Sachet',
                'satuan' => 'sachet',
                'satuan_beli' => 'renceng',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 10800, // Rp900/sachet
                'harga_jual' => 1500,
                'hpp_rata_rata' => 900,
                'stok' => 48,
                'stok_minimum' => 15,
                'stok_target' => 60,
            ],
            [
                'kategori' => 'Minuman',
                'kode' => 'P-011',
                'nama' => 'Air Mineral Botol 600ml',
                'satuan' => 'botol',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 24,
                'harga_beli' => 60000, // Rp2.500/btl
                'harga_jual' => 3500,
                'hpp_rata_rata' => 2500,
                'stok' => 72,
                'stok_minimum' => 24,
                'stok_target' => 96,
            ],
            [
                'kategori' => 'Minuman',
                'kode' => 'P-012',
                'nama' => 'Air Mineral Galon 19 L',
                'satuan' => 'galon',
                'satuan_beli' => 'galon',
                'isi_per_satuan_beli' => 1,
                'harga_beli' => 16000,
                'harga_jual' => 20000,
                'hpp_rata_rata' => 16000,
                'stok' => 12,
                'stok_minimum' => 5,
                'stok_target' => 20,
            ],
            [
                'kategori' => 'Sembako',
                'kode' => 'P-013',
                'nama' => 'Telur Ayam Ras 1 kg',
                'satuan' => 'kg',
                'satuan_beli' => 'peti',
                'isi_per_satuan_beli' => 10,
                'harga_beli' => 240000, // Rp24.000/kg
                'harga_jual' => 28000,
                'hpp_rata_rata' => 24000,
                'stok' => 15,
                'stok_minimum' => 8,
                'stok_target' => 30,
            ],
            [
                'kategori' => 'Sembako',
                'kode' => 'P-014',
                'nama' => 'Kecap Manis Refill 550ml',
                'satuan' => 'pouch',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 180000, // Rp15.000/pouch
                'harga_jual' => 18500,
                'hpp_rata_rata' => 15000,
                'stok' => 14,
                'stok_minimum' => 6,
                'stok_target' => 24,
            ],
            [
                'kategori' => 'Sembako',
                'kode' => 'P-015',
                'nama' => 'Saus Sambal Botol 335ml',
                'satuan' => 'botol',
                'satuan_beli' => 'lusin',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 132000, // Rp11.000/btl
                'harga_jual' => 14000,
                'hpp_rata_rata' => 11000,
                'stok' => 12,
                'stok_minimum' => 5,
                'stok_target' => 20,
            ],
            [
                'kategori' => 'Sembako',
                'kode' => 'P-016',
                'nama' => 'Garam Dapur Beryodium 250g',
                'satuan' => 'bungkus',
                'satuan_beli' => 'pak',
                'isi_per_satuan_beli' => 20,
                'harga_beli' => 40000, // Rp2.000/bks
                'harga_jual' => 3000,
                'hpp_rata_rata' => 2000,
                'stok' => 40,
                'stok_minimum' => 15,
                'stok_target' => 50,
            ],
            [
                'kategori' => 'Makanan Ringan',
                'kode' => 'P-017',
                'nama' => 'Biskuit Roma Kelapa 300g',
                'satuan' => 'bungkus',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 96000, // Rp8.000/bks
                'harga_jual' => 10500,
                'hpp_rata_rata' => 8000,
                'stok' => 18,
                'stok_minimum' => 6,
                'stok_target' => 24,
            ],
            [
                'kategori' => 'Makanan Ringan',
                'kode' => 'P-018',
                'nama' => 'Wafer Cokelat Kaleng 300g',
                'satuan' => 'kaleng',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 6,
                'harga_beli' => 150000, // Rp25.000/klg
                'harga_jual' => 32000,
                'hpp_rata_rata' => 25000,
                'stok' => 12,
                'stok_minimum' => 4,
                'stok_target' => 15,
            ],
            [
                'kategori' => 'Makanan Ringan',
                'kode' => 'P-019',
                'nama' => 'Keripik Singkong Balado 150g',
                'satuan' => 'bungkus',
                'satuan_beli' => 'bal',
                'isi_per_satuan_beli' => 10,
                'harga_beli' => 65000, // Rp6.500/bks
                'harga_jual' => 9000,
                'hpp_rata_rata' => 6500,
                'stok' => 15,
                'stok_minimum' => 5,
                'stok_target' => 25,
            ],
            [
                'kategori' => 'Kebutuhan Rumah',
                'kode' => 'P-020',
                'nama' => 'Deterjen Bubuk 800g',
                'satuan' => 'bungkus',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 180000, // Rp15.000/bks
                'harga_jual' => 18500,
                'hpp_rata_rata' => 15000,
                'stok' => 16,
                'stok_minimum' => 6,
                'stok_target' => 24,
            ],
            [
                'kategori' => 'Kebutuhan Rumah',
                'kode' => 'P-021',
                'nama' => 'Cairan Pencuci Piring 750ml',
                'satuan' => 'pouch',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 132000, // Rp11.000/pouch
                'harga_jual' => 13500,
                'hpp_rata_rata' => 11000,
                'stok' => 18,
                'stok_minimum' => 6,
                'stok_target' => 24,
            ],
            [
                'kategori' => 'Kebutuhan Rumah',
                'kode' => 'P-022',
                'nama' => 'Pembersih Lantai Wangi 750ml',
                'satuan' => 'pouch',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 120000, // Rp10.000/pouch
                'harga_jual' => 12500,
                'hpp_rata_rata' => 10000,
                'stok' => 15,
                'stok_minimum' => 5,
                'stok_target' => 20,
            ],
            [
                'kategori' => 'Rokok & Tembakau',
                'kode' => 'P-023',
                'nama' => 'Rokok Surya 16',
                'satuan' => 'bungkus',
                'satuan_beli' => 'slop',
                'isi_per_satuan_beli' => 10,
                'harga_beli' => 320000, // Rp32.000/bks
                'harga_jual' => 35000,
                'hpp_rata_rata' => 32000,
                'stok' => 25,
                'stok_minimum' => 10,
                'stok_target' => 40,
            ],
            [
                'kategori' => 'Rokok & Tembakau',
                'kode' => 'P-024',
                'nama' => 'Rokok Sampoerna Mild 16',
                'satuan' => 'bungkus',
                'satuan_beli' => 'slop',
                'isi_per_satuan_beli' => 10,
                'harga_beli' => 310000, // Rp31.000/bks
                'harga_jual' => 34000,
                'hpp_rata_rata' => 31000,
                'stok' => 20,
                'stok_minimum' => 10,
                'stok_target' => 40,
            ],
            [
                'kategori' => 'Rokok & Tembakau',
                'kode' => 'P-025',
                'nama' => 'Rokok Djarum Super 12',
                'satuan' => 'bungkus',
                'satuan_beli' => 'slop',
                'isi_per_satuan_beli' => 10,
                'harga_beli' => 230000, // Rp23.000/bks
                'harga_jual' => 26000,
                'hpp_rata_rata' => 23000,
                'stok' => 18,
                'stok_minimum' => 8,
                'stok_target' => 30,
            ],
            [
                'kategori' => 'Lain-lain',
                'kode' => 'P-026',
                'nama' => 'Korek Api Gas Roda',
                'satuan' => 'buah',
                'satuan_beli' => 'kotak',
                'isi_per_satuan_beli' => 50,
                'harga_beli' => 100000, // Rp2.000/bh
                'harga_jual' => 3000,
                'hpp_rata_rata' => 2000,
                'stok' => 45,
                'stok_minimum' => 15,
                'stok_target' => 60,
            ],
            [
                'kategori' => 'Lain-lain',
                'kode' => 'P-027',
                'nama' => 'Baterai AA 2pcs',
                'satuan' => 'pak',
                'satuan_beli' => 'hanger',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 84000, // Rp7.000/pak
                'harga_jual' => 9500,
                'hpp_rata_rata' => 7000,
                'stok' => 15,
                'stok_minimum' => 6,
                'stok_target' => 20,
            ],
            [
                'kategori' => 'Sembako',
                'kode' => 'P-028',
                'nama' => 'Tepung Terigu Serbaguna 1 kg',
                'satuan' => 'bungkus',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 12,
                'harga_beli' => 120000, // Rp10.000/kg
                'harga_jual' => 12000,
                'hpp_rata_rata' => 10000,
                'stok' => 24,
                'stok_minimum' => 10,
                'stok_target' => 36,
            ],
            [
                'kategori' => 'Sembako',
                'kode' => 'P-029',
                'nama' => 'Santan Kelapa Instan 65ml',
                'satuan' => 'kotak',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 36,
                'harga_beli' => 90000, // Rp2.500/ktk
                'harga_jual' => 3500,
                'hpp_rata_rata' => 2500,
                'stok' => 35,
                'stok_minimum' => 15,
                'stok_target' => 50,
            ],
            [
                'kategori' => 'Minuman',
                'kode' => 'P-030',
                'nama' => 'Minuman Isotonik Botol 500ml',
                'satuan' => 'botol',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 24,
                'harga_beli' => 144000, // Rp6.000/btl
                'harga_jual' => 8000,
                'hpp_rata_rata' => 6000,
                'stok' => 30,
                'stok_minimum' => 12,
                'stok_target' => 48,
            ],
        ];

        $produkEntities = [];
        foreach ($produkMaster as $pm) {
            $kat = $kategoris[$pm['kategori']];
            $stokAwal = $pm['stok'];

            $created = ! Produk::where('kode', $pm['kode'])->exists();
            $produk = Produk::firstOrCreate(
                ['kode' => $pm['kode']],
                [
                    'kategori_id' => $kat->id,
                    'nama' => $pm['nama'],
                    'satuan' => $pm['satuan'],
                    'satuan_beli' => $pm['satuan_beli'],
                    'isi_per_satuan_beli' => $pm['isi_per_satuan_beli'],
                    'harga_beli' => $pm['harga_beli'],
                    'harga_jual' => $pm['harga_jual'],
                    'hpp_rata_rata' => $pm['hpp_rata_rata'],
                    'stok' => $stokAwal,
                    'stok_minimum' => $pm['stok_minimum'],
                    'stok_target' => $pm['stok_target'],
                    'status' => 'aktif',
                ]
            );

            // Catat pergerakan stok awal agar audit:stok (IK-03) langsung seimbang
            if ($created) {
                PergerakanStok::create([
                    'produk_id' => $produk->id,
                    'user_id' => $users['admin']->id,
                    'jenis' => 'stok_awal',
                    'qty' => $stokAwal,
                    'stok_sesudah' => $stokAwal,
                    'sumber_tipe' => null,
                    'sumber_id' => null,
                    'created_at' => now()->subDays(15),
                ]);
            }

            $produkEntities[] = $produk;
        }

        // 6. Buat Sample Purchase Order (PO)
        $pembelianService = app(PembelianService::class);

        // PO 1: Draf PO (untuk demonstrasi langkah 2 & 3: Gudang lihat rekomendasi & Admin setujui)
        $pembelianService->buatDraf([
            'supplier_id' => $suppliers[0]->id,
            'jenis_bayar' => 'kredit',
            'tanggal' => date('Y-m-d', strtotime('-2 days')),
            'jatuh_tempo' => date('Y-m-d', strtotime('+12 days')),
            'items' => [
                ['produk_id' => $produkEntities[0]->id, 'qty_pesan' => 2, 'harga_beli' => 64000],
                ['produk_id' => $produkEntities[1]->id, 'qty_pesan' => 2, 'harga_beli' => 192000],
            ],
        ], $users['gudang']);

        // PO 2: PO sudah disetujui (siap untuk demonstrasi penerimaan barang di langkah 4)
        $poDisetujui = $pembelianService->buatDraf([
            'supplier_id' => $suppliers[1]->id,
            'jenis_bayar' => 'kredit',
            'tanggal' => date('Y-m-d', strtotime('-3 days')),
            'jatuh_tempo' => date('Y-m-d', strtotime('+7 days')),
            'items' => [
                ['produk_id' => $produkEntities[4]->id, 'qty_pesan' => 2, 'harga_beli' => 120000], // 2 dus mie goreng
                ['produk_id' => $produkEntities[10]->id, 'qty_pesan' => 3, 'harga_beli' => 60000], // 3 dus air mineral
            ],
        ], $users['gudang']);
        $pembelianService->setujui($poDisetujui, $users['admin']);
        $pembelianService->tandaiDikirim($poDisetujui, $users['admin']);

        // 7. Buat Riwayat Penjualan 14 Hari Terakhir via PenjualanService
        $penjualanService = app(PenjualanService::class);
        $kasirUser = $users['kasir1'];

        // Beberapa transaksi realistis selama 14 hari
        for ($hari = 13; $hari >= 1; $hari--) {
            $tglTransaksi = date('Y-m-d', strtotime("-{$hari} days"));
            $jumlahTrx = rand(2, 4);

            for ($trx = 1; $trx <= $jumlahTrx; $trx++) {
                // Pilih 1 - 3 produk acak dari produk stok banyak (P-005 ke atas)
                $pIndex1 = rand(4, 15);
                $pIndex2 = rand(16, 25);

                $items = [
                    ['produk_id' => $produkEntities[$pIndex1]->id, 'qty' => rand(1, 2)],
                    ['produk_id' => $produkEntities[$pIndex2]->id, 'qty' => rand(1, 3)],
                ];

                $totalEst = 0;
                foreach ($items as $it) {
                    $prod = Produk::find($it['produk_id']);
                    $totalEst += $it['qty'] * (float) $prod->harga_jual;
                }

                $metodeList = ['tunai', 'tunai', 'qris', 'transfer'];
                $metode = $metodeList[array_rand($metodeList)];
                $dibayar = ($metode === 'tunai') ? ceil($totalEst / 10000) * 10000 : $totalEst;
                if ($dibayar < $totalEst) {
                    $dibayar = $totalEst;
                }

                try {
                    $penjualanService->simpan([
                        'key' => 'SEED-TRX-' . $tglTransaksi . '-' . $trx . '-' . uniqid(),
                        'pelanggan_id' => null,
                        'jenis_bayar' => $metode,
                        'jumlah_dibayar' => $dibayar,
                        'tanggal' => $tglTransaksi,
                        'items' => $items,
                    ], $kasirUser);
                } catch (\Exception $e) {
                    // Abaikan bila ada batas stok saat generate data acak
                }
            }
        }

        // 8. Pengeluaran Operasional
        $biayaList = [
            ['kategori_biaya' => 'listrik', 'nominal' => 450000, 'tanggal' => date('Y-m-d', strtotime('-10 days')), 'keterangan' => 'Tagihan listrik PLN bulan berjalan'],
            ['kategori_biaya' => 'kemasan', 'nominal' => 75000, 'tanggal' => date('Y-m-d', strtotime('-7 days')), 'keterangan' => 'Beli kantong plastik kresek hitam dan putih'],
            ['kategori_biaya' => 'transport', 'nominal' => 50000, 'tanggal' => date('Y-m-d', strtotime('-4 days')), 'keterangan' => 'Bensin armada antar sembako'],
            ['kategori_biaya' => 'lain-lain', 'nominal' => 35000, 'tanggal' => date('Y-m-d', strtotime('-2 days')), 'keterangan' => 'Beli sabun pembersih lantai dan lap toko'],
        ];

        foreach ($biayaList as $b) {
            Pengeluaran::create([
                'user_id' => $users['keuangan']->id,
                'kategori_biaya' => $b['kategori_biaya'],
                'nominal' => $b['nominal'],
                'tanggal' => $b['tanggal'],
                'keterangan' => $b['keterangan'],
            ]);
        }
    }
}
