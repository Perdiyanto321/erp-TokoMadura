<?php

namespace App\Services;

use App\Exceptions\AksiTidakDiizinkan;
use App\Models\PenyesuaianStok;
use App\Models\Produk;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PenyesuaianStokService
{
    public function __construct(
        protected InventoriService $inventoriService
    ) {}

    public function ajukan(Produk $produk, int $qtySelisih, string $alasan, User $gudang): PenyesuaianStok
    {
        if (trim($alasan) === '') {
            throw new AksiTidakDiizinkan("Alasan penyesuaian stok wajib diisi.");
        }

        if ($qtySelisih === 0) {
            throw new AksiTidakDiizinkan("Selisih kuantitas tidak boleh 0.");
        }

        $penyesuaian = PenyesuaianStok::create([
            'produk_id' => $produk->id,
            'diajukan_oleh' => $gudang->id,
            'disetujui_oleh' => null,
            'qty_selisih' => $qtySelisih,
            'alasan' => $alasan,
            'status' => 'menunggu',
        ]);

        LogAktivitasService::catat(
            'penyesuaian.ajukan',
            'PenyesuaianStok',
            $penyesuaian->id,
            ['produk' => $produk->nama, 'qty_selisih' => $qtySelisih],
            $gudang
        );

        return $penyesuaian;
    }

    public function setujui(PenyesuaianStok $penyesuaian, User $admin): PenyesuaianStok
    {
        return DB::transaction(function () use ($penyesuaian, $admin) {
            $pTerkunci = PenyesuaianStok::where('id', $penyesuaian->id)->lockForUpdate()->first();

            if ($pTerkunci->status !== 'menunggu') {
                throw new AksiTidakDiizinkan("Penyesuaian stok sudah diproses sebelumnya.");
            }

            // Ubah stok via InventoriService
            $this->inventoriService->ubahStok(
                $pTerkunci->produk,
                $pTerkunci->qty_selisih,
                'penyesuaian',
                $pTerkunci,
                $admin
            );

            $pTerkunci->update([
                'status' => 'disetujui',
                'disetujui_oleh' => $admin->id,
            ]);

            LogAktivitasService::catat(
                'penyesuaian.setujui',
                'PenyesuaianStok',
                $pTerkunci->id,
                ['produk' => $pTerkunci->produk->nama, 'qty_selisih' => $pTerkunci->qty_selisih],
                $admin
            );

            return $pTerkunci;
        });
    }

    public function tolak(PenyesuaianStok $penyesuaian, User $admin): PenyesuaianStok
    {
        return DB::transaction(function () use ($penyesuaian, $admin) {
            $pTerkunci = PenyesuaianStok::where('id', $penyesuaian->id)->lockForUpdate()->first();

            if ($pTerkunci->status !== 'menunggu') {
                throw new AksiTidakDiizinkan("Penyesuaian stok sudah diproses sebelumnya.");
            }

            $pTerkunci->update([
                'status' => 'ditolak',
                'disetujui_oleh' => $admin->id,
            ]);

            LogAktivitasService::catat(
                'penyesuaian.tolak',
                'PenyesuaianStok',
                $pTerkunci->id,
                ['produk' => $pTerkunci->produk->nama],
                $admin
            );

            return $pTerkunci;
        });
    }
}
