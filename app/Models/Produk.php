<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produk extends Model
{
    use HasFactory;

    protected $table = 'produk';

    protected $fillable = [
        'kategori_id',
        'kode',
        'nama',
        'satuan',
        'satuan_beli',
        'isi_per_satuan_beli',
        'harga_beli',
        'harga_jual',
        'hpp_rata_rata',
        'stok',
        'stok_minimum',
        'stok_target',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'isi_per_satuan_beli' => 'integer',
            'harga_beli' => 'decimal:2',
            'harga_jual' => 'decimal:2',
            'hpp_rata_rata' => 'decimal:2',
            'stok' => 'integer',
            'stok_minimum' => 'integer',
            'stok_target' => 'integer',
        ];
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'kategori_id');
    }

    public function pergerakanStok(): HasMany
    {
        return $this->hasMany(PergerakanStok::class, 'produk_id');
    }

    public function scopeDibawahMinimum(Builder $query): Builder
    {
        return $query->whereColumn('stok', '<=', 'stok_minimum');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }
}
