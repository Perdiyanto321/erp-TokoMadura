<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->nullable()->constrained('penjualan')->restrictOnDelete();
            $table->foreignId('pembelian_id')->nullable()->constrained('pembelian')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('arah'); // masuk, keluar
            $table->decimal('jumlah', 15, 2);
            $table->date('tanggal');
            $table->string('metode'); // tunai, transfer, qris
            $table->timestamps();

            $table->index(['tanggal', 'arah']);
        });

        DB::statement('ALTER TABLE pembayaran ADD CONSTRAINT chk_pembayaran_jumlah CHECK (jumlah > 0)');
        DB::statement('ALTER TABLE pembayaran ADD CONSTRAINT chk_pembayaran_fk CHECK ((penjualan_id IS NOT NULL AND pembelian_id IS NULL) OR (penjualan_id IS NULL AND pembelian_id IS NOT NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
