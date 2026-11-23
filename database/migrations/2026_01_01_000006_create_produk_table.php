<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('produk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori')->restrictOnDelete();
            $table->string('kode')->unique();
            $table->string('nama')->index();
            $table->string('satuan');
            $table->string('satuan_beli');
            $table->integer('isi_per_satuan_beli')->default(1);
            $table->decimal('harga_beli', 15, 2)->default(0);
            $table->decimal('harga_jual', 15, 2)->default(0);
            $table->decimal('hpp_rata_rata', 15, 2)->default(0);
            $table->integer('stok')->default(0);
            $table->integer('stok_minimum')->default(0);
            $table->integer('stok_target')->default(0);
            $table->string('status')->default('aktif');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE produk ADD CONSTRAINT chk_produk_stok CHECK (stok >= 0)');
        DB::statement('ALTER TABLE produk ADD CONSTRAINT chk_produk_harga_jual CHECK (harga_jual >= 0)');
        DB::statement('ALTER TABLE produk ADD CONSTRAINT chk_produk_harga_beli CHECK (harga_beli >= 0)');
        DB::statement('ALTER TABLE produk ADD CONSTRAINT chk_produk_isi CHECK (isi_per_satuan_beli >= 1)');
        DB::statement('ALTER TABLE produk ADD CONSTRAINT chk_produk_stok_target CHECK (stok_target >= stok_minimum)');
    }

    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
