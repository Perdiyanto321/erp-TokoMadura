<?php

namespace App\Services;

use App\Exceptions\AksiTidakDiizinkan;
use App\Models\Pembayaran;
use App\Models\Penjualan;
use App\Models\User;
use App\Support\Rupiah;
use Illuminate\Support\Facades\DB;

class KasbonService
{
    /**
     * Bayar / cicil piutang kasbon atas penjualan tertentu.
     */
    public function bayarKasbon(
        Penjualan $penjualan,
        float $jumlah,
        string $metode,
        User $user,
        ?string $tanggal = null
    ): Pembayaran {
        return DB::transaction(function () use ($penjualan, $jumlah, $metode, $user, $tanggal) {
            $tgl = $tanggal ?? date('Y-m-d');

            $penjualanTerkunci = Penjualan::where('id', $penjualan->id)
                ->with('pembayaran')
                ->lockForUpdate()
                ->first();

            if ($penjualanTerkunci->jenis_bayar !== 'kasbon') {
                throw new AksiTidakDiizinkan("Penjualan ini bukan transaksi kasbon.");
            }

            if ($penjualanTerkunci->status !== 'selesai') {
                throw new AksiTidakDiizinkan("Penjualan tidak berstatus selesai.");
            }

            if ($jumlah <= 0) {
                throw new AksiTidakDiizinkan("Jumlah pembayaran harus lebih besar dari 0.");
            }

            $sudahDibayar = (float) $penjualanTerkunci->pembayaran->where('arah', 'masuk')->sum('jumlah');
            $sisaPiutang = max(0, (float) $penjualanTerkunci->total - $sudahDibayar);

            if ($sisaPiutang <= 0) {
                throw new AksiTidakDiizinkan("Kasbon transaksi ini sudah lunas.");
            }

            if ($jumlah > $sisaPiutang) {
                $jumlahRp = Rupiah::format($jumlah);
                $sisaRp = Rupiah::format($sisaPiutang);
                throw new AksiTidakDiizinkan("Pembayaran {$jumlahRp} melebihi sisa piutang {$sisaRp}.");
            }

            $pembayaran = Pembayaran::create([
                'penjualan_id' => $penjualanTerkunci->id,
                'pembelian_id' => null,
                'user_id' => $user->id,
                'arah' => 'masuk',
                'jumlah' => $jumlah,
                'tanggal' => $tgl,
                'metode' => $metode,
            ]);

            LogAktivitasService::catat(
                'kasbon.bayar',
                'Pembayaran',
                $pembayaran->id,
                [
                    'nomor_penjualan' => $penjualanTerkunci->nomor,
                    'jumlah' => $jumlah,
                    'sisa_sebelum' => $sisaPiutang,
                ],
                $user
            );

            return $pembayaran;
        });
    }
}
