<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kasir_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggan')->restrictOnDelete();
            $table->string('nomor')->unique();
            $table->date('tanggal');
            $table->decimal('total', 15, 2);
            $table->string('jenis_bayar'); // tunai, transfer, qris, kasbon
            $table->decimal('jumlah_dibayar', 15, 2)->default(0);
            $table->string('status')->default('selesai'); // selesai, batal
            $table->string('idempotency_key')->unique();
            $table->text('alasan_batal')->nullable();
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['tanggal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan');
    }
};
