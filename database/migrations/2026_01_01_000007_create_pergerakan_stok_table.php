<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pergerakan_stok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('jenis'); // stok_awal, penerimaan, penjualan, retur, penyesuaian, pembatalan
            $table->integer('qty'); // signed (+ masuk, - keluar)
            $table->integer('stok_sesudah');
            $table->string('sumber_tipe')->nullable();
            $table->unsignedBigInteger('sumber_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['produk_id', 'created_at']);
            $table->index(['sumber_tipe', 'sumber_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pergerakan_stok');
    }
};
