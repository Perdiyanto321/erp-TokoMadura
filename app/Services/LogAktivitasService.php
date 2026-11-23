<?php

namespace App\Services;

use App\Models\LogAktivitas;
use App\Models\User;

class LogAktivitasService
{
    /**
     * Catat aksi ke dalam log aktivitas audit.
     *
     * @param string $aksi Jenis aksi (mis. 'penjualan.simpan', 'po.setujui', 'akses.ditolak')
     * @param string|null $entitas Nama entitas / model terkait
     * @param int|null $entitasId ID entitas terkait
     * @param array<string, mixed>|null $detail Nilai lama/baru atau informasi tambahan
     * @param User|null $user Pengguna yang melakukan aksi
     * @return LogAktivitas
     */
    public static function catat(
        string $aksi,
        ?string $entitas = null,
        ?int $entitasId = null,
        ?array $detail = null,
        ?User $user = null
    ): LogAktivitas {
        $userId = $user?->id ?? auth()->id();

        return LogAktivitas::create([
            'user_id' => $userId,
            'aksi' => $aksi,
            'entitas' => $entitas,
            'entitas_id' => $entitasId,
            'detail' => $detail,
            'waktu' => now(),
        ]);
    }
}
