<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailRetur extends Model
{
    use HasFactory;

    protected $table = 'detail_retur';

    protected $fillable = [
        'retur_id',
        'detail_penjualan_id',
        'qty',
        'kondisi',
    ];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
        ];
    }

    public function retur(): BelongsTo
    {
        return $this->belongsTo(ReturPenjualan::class, 'retur_id');
    }

    public function detailPenjualan(): BelongsTo
    {
        return $this->belongsTo(DetailPenjualan::class, 'detail_penjualan_id');
    }
}
