<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailPenerimaan extends Model
{
    use HasFactory;

    protected $table = 'detail_penerimaan';

    protected $fillable = [
        'penerimaan_id',
        'detail_pembelian_id',
        'qty_diterima',
    ];

    protected function casts(): array
    {
        return [
            'qty_diterima' => 'integer',
        ];
    }

    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(PenerimaanBarang::class, 'penerimaan_id');
    }

    public function detailPembelian(): BelongsTo
    {
        return $this->belongsTo(DetailPembelian::class, 'detail_pembelian_id');
    }
}
