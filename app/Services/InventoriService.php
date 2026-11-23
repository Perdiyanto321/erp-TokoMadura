<?php

namespace App\Services;

use App\Exceptions\StokTidakCukup;
use App\Models\PergerakanStok;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoriService
{
    /**
     * Ubah stok produk dan catat riwayat pergerakan stok secara atomik.
     * Satu-satunya pintu perubahan stok produk (D-03).
     *
     * @param Produk $produk
     * @param int $qty Perubahan kuantitas (+ masuk, - keluar)
     * @param string $jenis Jenis pergerakan (stok_awal, penerimaan, penjualan, retur, penyesuaian, pembatalan)
     * @param Model|null $sumber Model asal (Penjualan, PenerimaanBarang, PenyesuaianStok, dll)
     * @param User|null $user Pengguna pelaksana
     * @return PergerakanStok
     * @throws StokTidakCukup
     */
    public function ubahStok(
        Produk $produk,
        int $qty,
        string $jenis,
        ?Model $sumber = null,
        ?User $user = null
    ): PergerakanStok {
        return DB::transaction(function () use ($produk, $qty, $jenis, $sumber, $user) {
            // Kunci baris produk
            $terkunci = Produk::where('id', $produk->id)->lockForUpdate()->first();

            $stokBaru = $terkunci->stok + $qty;

            if ($stokBaru < 0) {
                throw new StokTidakCukup($terkunci, abs($qty), $terkunci->stok);
            }

            $terkunci->stok = $stokBaru;
            $terkunci->save();

            // Perbarui instance yang dioper
            $produk->stok = $stokBaru;

            $sumberTipe = $sumber ? class_basename($sumber) : null;
            $sumberId = $sumber?->getKey();

            return PergerakanStok::create([
                'produk_id' => $terkunci->id,
                'user_id' => $user?->id ?? auth()->id() ?? 1,
                'jenis' => $jenis,
                'qty' => $qty,
                'stok_sesudah' => $stokBaru,
                'sumber_tipe' => $sumberTipe,
                'sumber_id' => $sumberId,
                'created_at' => now(),
            ]);
        });
    }
}
