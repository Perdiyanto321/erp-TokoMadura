<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuatPoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:supplier,id'],
            'jenis_bayar' => ['required', 'in:tunai,kredit'],
            'tanggal' => ['nullable', 'date'],
            'jatuh_tempo' => ['nullable', 'required_if:jenis_bayar,kredit', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'exists:produk,id'],
            'items.*.qty_pesan' => ['required', 'integer', 'min:1'],
            'items.*.harga_beli' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
