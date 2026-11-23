<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditStokCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:stok';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Audit konsistensi fisik stok produk dengan akumulasi riwayat pergerakan stok (IK-03)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Menjalankan audit konsistensi stok (IK-03)...");

        $selisih = DB::select("
            SELECT p.id, p.kode, p.nama, p.stok, COALESCE(SUM(m.qty), 0) AS jumlah_pergerakan
            FROM produk p
            LEFT JOIN pergerakan_stok m ON m.produk_id = p.id
            GROUP BY p.id, p.kode, p.nama, p.stok
            HAVING p.stok <> COALESCE(SUM(m.qty), 0)
        ");

        if (empty($selisih)) {
            $this->info("✓ AUDIT BERHASIL: 0 produk berselisih. Seluruh stok cocok sempurna dengan riwayat pergerakan (IK-03 terpenuhi).");
            return Command::SUCCESS;
        }

        $this->error("✗ DITEMUKAN SELISIH STOK PADA " . count($selisih) . " PRODUK:");

        $rows = array_map(function ($item) {
            return [
                'ID' => $item->id,
                'Kode' => $item->kode,
                'Nama' => $item->nama,
                'Stok Fisik' => $item->stok,
                'Jml Pergerakan' => $item->jumlah_pergerakan,
                'Selisih' => $item->stok - $item->jumlah_pergerakan,
            ];
        }, $selisih);

        $this->table(['ID', 'Kode', 'Nama', 'Stok Fisik', 'Jml Pergerakan', 'Selisih'], $rows);

        return Command::FAILURE;
    }
}
