<?php

namespace Database\Seeders;

use App\Models\Kategori;
use App\Models\PergerakanStok;
use App\Models\Produk;
use App\Models\User;
use App\Services\PenjualanService;
use Illuminate\Database\Seeder;

class KinerjaSeeder extends Seeder
{
    /**
     * Seeder skala besar untuk pengujian kinerja (IK-07) dan audit (IK-03).
     */
    public function run(): void
    {
        $admin = User::firstWhere('username', 'admin') ?? User::first();
        $kasir = User::firstWhere('username', 'kasir1') ?? $admin;
        $kategoriList = Kategori::all();

        if ($kategoriList->isEmpty()) {
            $this->call(DemoSeeder::class);
            $kategoriList = Kategori::all();
        }

        $existingCount = Produk::count();
        $targetProduk = 1000;
        $tambah = max(0, $targetProduk - $existingCount);

        $this->command->info("Menambahkan {$tambah} produk untuk kinerja...");

        $produkBaru = [];
        for ($i = 1; $i <= $tambah; $i++) {
            $num = $existingCount + $i;
            $kode = 'P-K' . str_pad((string) $num, 5, '0', STR_PAD_LEFT);
            $kat = $kategoriList->random();
            $stokAwal = rand(20, 100);
            $hargaBeli = rand(10, 200) * 1000;
            $hargaJual = $hargaBeli + (rand(1, 5) * 1000);

            $p = Produk::create([
                'kategori_id' => $kat->id,
                'kode' => $kode,
                'nama' => "Produk Kinerja {$num}",
                'satuan' => 'pcs',
                'satuan_beli' => 'dus',
                'isi_per_satuan_beli' => 1,
                'harga_beli' => $hargaBeli,
                'harga_jual' => $hargaJual,
                'hpp_rata_rata' => $hargaBeli,
                'stok' => $stokAwal,
                'stok_minimum' => 10,
                'stok_target' => 50,
                'status' => 'aktif',
            ]);

            PergerakanStok::create([
                'produk_id' => $p->id,
                'user_id' => $admin->id,
                'jenis' => 'stok_awal',
                'qty' => $stokAwal,
                'stok_sesudah' => $stokAwal,
                'sumber_tipe' => null,
                'sumber_id' => null,
                'created_at' => now()->subDays(30),
            ]);

            $produkBaru[] = $p;
        }

        $this->command->info("Selesai membuat produk. Sekarang membuat transaksi simulasi...");

        $penjualanService = app(PenjualanService::class);
        $semuaProduk = Produk::where('stok', '>=', 5)->get();

        // Buat 100 transaksi tambahan dengan multi-item
        $targetTrx = 100;
        for ($t = 1; $t <= $targetTrx; $t++) {
            $sample = $semuaProduk->random(rand(1, 3));
            $items = [];
            $totalEst = 0;
            foreach ($sample as $sp) {
                $qty = rand(1, 2);
                $items[] = ['produk_id' => $sp->id, 'qty' => $qty];
                $totalEst += $qty * (float) $sp->harga_jual;
            }

            try {
                $penjualanService->simpan([
                    'key' => 'KINERJA-TRX-' . $t . '-' . uniqid(),
                    'pelanggan_id' => null,
                    'jenis_bayar' => 'tunai',
                    'jumlah_dibayar' => $totalEst + 5000,
                    'tanggal' => date('Y-m-d'),
                    'items' => $items,
                ], $kasir);
            } catch (\Exception $e) {
                // Lewati jika stok berubah
            }
        }

        $this->command->info("KinerjaSeeder selesai dijalankan.");
    }
}
