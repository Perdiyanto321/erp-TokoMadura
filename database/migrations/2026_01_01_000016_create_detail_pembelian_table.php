<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detail_pembelian', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembelian_id')->constrained('pembelian')->restrictOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->integer('qty_pesan'); // dalam satuan_beli
            $table->decimal('harga_beli', 15, 2); // per satuan_beli
            $table->timestamps();
        });

        DB::statement('ALTER TABLE detail_pembelian ADD CONSTRAINT chk_detail_pembelian_qty CHECK (qty_pesan > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pembelian');
    }
};
