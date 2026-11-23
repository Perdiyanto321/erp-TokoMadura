<?php

namespace App\Services;

use App\Exceptions\AksiTidakDiizinkan;
use App\Exceptions\BatasKasbonTerlampaui;
use App\Exceptions\PembayaranKurang;
use App\Exceptions\StokTidakCukup;
use App\Models\DetailPenjualan;
use App\Models\Pelanggan;
use App\Models\Pembayaran;
use App\Models\Penjualan;
use App\Models\Produk;
use App\Models\User;
use App\Support\NomorDokumen;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class PenjualanService
{
    public function __construct(
        protected InventoriService $inventoriService
    ) {}

    /**
     * Simpan transaksi penjualan secara atomik dengan penguncian baris produk.
     *
     * @param array{
     *     key: string,
     *     pelanggan_id?: int|null,
     *     jenis_bayar: string,
     *     jumlah_dibayar: float|int,
     *     tanggal?: string|null,
     *     items: array<int, array{produk_id: int, qty: int}>
     * } $data
     * @param User $kasir
     * @return Penjualan
     * @throws StokTidakCukup|PembayaranKurang|BatasKasbonTerlampaui
     */
    public function simpan(array $data, User $kasir): Penjualan
    {
        try {
            return DB::transaction(function () use ($data, $kasir) {
                $idempotencyKey = $data['key'];

                // 1. Idempotensi: jika sudah tersimpan sebelumnya, kembalikan transaksi tersebut
                $ada = Penjualan::where('idempotency_key', $idempotencyKey)->first();
                if ($ada) {
                    return $ada;
                }

                if (empty($data['items'])) {
                    throw new AksiTidakDiizinkan("Keranjang belanja tidak boleh kosong.");
                }

                // 2. Gabungkan baris produk yang sama, urutkan ID untuk mencegah deadlock (BR-02)
                $itemGroup = collect($data['items'])
                    ->groupBy('produk_id')
                    ->map(fn ($g) => $g->sum('qty'));

                $produkList = Produk::whereIn('id', $itemGroup->keys())
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                // 3. Validasi stok dalam kunci (BR-01) dan hitung total di server
                $total = 0;
                foreach ($itemGroup as $id => $qty) {
                    if (!isset($produkList[$id])) {
                        throw new AksiTidakDiizinkan("Produk ID {$id} tidak ditemukan.");
                    }

                    $p = $produkList[$id];
                    if ($p->stok < $qty) {
                        throw new StokTidakCukup($p, $qty, $p->stok);
                    }

                    $total += $qty * (float) $p->harga_jual;
                }

                $jenisBayar = strtolower($data['jenis_bayar']);
                $jumlahDibayar = (float) ($data['jumlah_dibayar'] ?? 0);
                $pelangganId = $data['pelanggan_id'] ?? null;
                $tanggal = $data['tanggal'] ?? date('Y-m-d');

                // 4. Validasi pembayaran
                if ($jenisBayar === 'kasbon') {
                    if (!$pelangganId) {
                        throw new AksiTidakDiizinkan("Penjualan kasbon wajib memilih pelanggan.");
                    }
                    $pelanggan = Pelanggan::findOrFail($pelangganId);
                    
                    // Hitung piutang belum lunas
                    $piutangLama = (float) Penjualan::where('pelanggan_id', $pelangganId)
                        ->where('jenis_bayar', 'kasbon')
                        ->where('status', 'selesai')
                        ->sum('total') - (float) Pembayaran::whereHas('penjualan', function ($q) use ($pelangganId) {
                            $q->where('pelanggan_id', $pelangganId)->where('status', 'selesai');
                        })->where('arah', 'masuk')->sum('jumlah');

                    $akanMenjadi = $piutangLama + $total;
                    if ($akanMenjadi > (float) $pelanggan->batas_kasbon) {
                        throw new BatasKasbonTerlampaui($pelanggan, $akanMenjadi, (float) $pelanggan->batas_kasbon);
                    }
                    $jumlahDibayar = 0;
                } else {
                    if ($jumlahDibayar < $total) {
                        throw new PembayaranKurang($total - $jumlahDibayar);
                    }
                }

                // 5. Simpan transaksi penjualan
                $penjualan = Penjualan::create([
                    'kasir_id' => $kasir->id,
                    'pelanggan_id' => $pelangganId,
                    'nomor' => $idempotencyKey, // nomor sementara
                    'tanggal' => $tanggal,
                    'total' => $total,
                    'jenis_bayar' => $jenisBayar,
                    'jumlah_dibayar' => $jumlahDibayar,
                    'status' => 'selesai',
                    'idempotency_key' => $idempotencyKey,
                ]);

                // Update nomor permanen terstandar TRX-YYYYMMDD-000123
                $nomorPermanen = NomorDokumen::generate('TRX', $penjualan->id, $tanggal);
                $penjualan->update(['nomor' => $nomorPermanen]);

                // 6. Simpan detail (snapshot harga_jual & hpp) dan potong stok via InventoriService
                foreach ($itemGroup as $id => $qty) {
                    $p = $produkList[$id];

                    DetailPenjualan::create([
                        'penjualan_id' => $penjualan->id,
                        'produk_id' => $p->id,
                        'qty' => $qty,
                        'harga_jual' => $p->harga_jual,
                        'hpp' => $p->hpp_rata_rata,
                    ]);

                    $this->inventoriService->ubahStok($p, -$qty, 'penjualan', $penjualan, $kasir);
                }

                // 7. Catat pembayaran masuk jika bukan kasbon
                if ($jenisBayar !== 'kasbon') {
                    Pembayaran::create([
                        'penjualan_id' => $penjualan->id,
                        'pembelian_id' => null,
                        'user_id' => $kasir->id,
                        'arah' => 'masuk',
                        'jumlah' => $total, // nilai masuk ke kas adalah total belanja (kembalian bukan kas)
                        'tanggal' => $tanggal,
                        'metode' => $jenisBayar,
                    ]);
                }

                // 8. Catat log aktivitas audit
                LogAktivitasService::catat(
                    'penjualan.simpan',
                    'Penjualan',
                    $penjualan->id,
                    [
                        'nomor' => $nomorPermanen,
                        'total' => $total,
                        'jenis_bayar' => $jenisBayar,
                        'jumlah_item' => count($itemGroup),
                    ],
                    $kasir
                );

                return $penjualan;
            });
        } catch (UniqueConstraintViolationException) {
            // Klik ganda yang lolos bersamaan
            return Penjualan::where('idempotency_key', $data['key'])->firstOrFail();
        }
    }

    /**
     * Batalkan penjualan dan kembalikan stok.
     */
    public function batalkan(Penjualan $penjualan, string $alasan, User $admin): Penjualan
    {
        return DB::transaction(function () use ($penjualan, $alasan, $admin) {
            if ($penjualan->status === 'batal') {
                return $penjualan;
            }

            // Kembalikan stok setiap item
            $penjualan->load('detailPenjualan.produk');
            foreach ($penjualan->detailPenjualan as $detail) {
                $this->inventoriService->ubahStok(
                    $detail->produk,
                    $detail->qty,
                    'pembatalan',
                    $penjualan,
                    $admin
                );
            }

            // Jika ada pembayaran masuk, buat pembayaran keluar koreksi
            if ($penjualan->jenis_bayar !== 'kasbon') {
                Pembayaran::create([
                    'penjualan_id' => $penjualan->id,
                    'pembelian_id' => null,
                    'user_id' => $admin->id,
                    'arah' => 'keluar',
                    'jumlah' => $penjualan->total,
                    'tanggal' => date('Y-m-d'),
                    'metode' => $penjualan->jenis_bayar,
                ]);
            }

            $penjualan->update([
                'status' => 'batal',
                'alasan_batal' => $alasan,
                'dibatalkan_oleh' => $admin->id,
            ]);

            LogAktivitasService::catat(
                'penjualan.batal',
                'Penjualan',
                $penjualan->id,
                ['nomor' => $penjualan->nomor, 'alasan' => $alasan],
                $admin
            );

            return $penjualan;
        });
    }
}
