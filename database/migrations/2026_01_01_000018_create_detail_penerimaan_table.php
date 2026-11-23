<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('detail_penerimaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerimaan_id')->constrained('penerimaan_barang')->restrictOnDelete();
            $table->foreignId('detail_pembelian_id')->constrained('detail_pembelian')->restrictOnDelete();
            $table->integer('qty_diterima'); // dalam satuan_beli
            $table->timestamps();
        });

        DB::statement('ALTER TABLE detail_penerimaan ADD CONSTRAINT chk_detail_penerimaan_qty CHECK (qty_diterima > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_penerimaan');
    }
};
