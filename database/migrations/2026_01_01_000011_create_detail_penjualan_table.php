<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detail_penjualan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualan')->restrictOnDelete();
            $table->foreignId('produk_id')->constrained('produk')->restrictOnDelete();
            $table->integer('qty');
            $table->decimal('harga_jual', 15, 2);
            $table->decimal('hpp', 15, 2);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE detail_penjualan ADD CONSTRAINT chk_detail_penjualan_qty CHECK (qty > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_penjualan');
    }
};
