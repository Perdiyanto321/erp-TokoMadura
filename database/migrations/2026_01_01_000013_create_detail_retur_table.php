<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detail_retur', function (Blueprint $table) {
            $table->id();
            $table->foreignId('retur_id')->constrained('retur_penjualan')->restrictOnDelete();
            $table->foreignId('detail_penjualan_id')->constrained('detail_penjualan')->restrictOnDelete();
            $table->integer('qty');
            $table->string('kondisi'); // layak_jual, rusak
            $table->timestamps();
        });

        DB::statement('ALTER TABLE detail_retur ADD CONSTRAINT chk_detail_retur_qty CHECK (qty > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_retur');
    }
};
