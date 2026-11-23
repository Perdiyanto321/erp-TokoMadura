<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PergerakanStok extends Model
{
    const UPDATED_AT = null;

    protected $table = 'pergerakan_stok';

    protected $fillable = [
        'produk_id',
        'user_id',
        'jenis',
        'qty',
        'stok_sesudah',
        'sumber_tipe',
        'sumber_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'stok_sesudah' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function produk(): BelongsTo
    {
        return $this->belongsTo(Produk::class, 'produk_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
