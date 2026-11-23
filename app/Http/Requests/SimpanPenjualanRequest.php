<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SimpanPenjualanRequest extends FormRequest
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
            'key' => ['required', 'string'],
            'jenis_bayar' => ['required', 'in:tunai,transfer,qris,kasbon'],
            'jumlah_dibayar' => ['nullable', 'numeric', 'min:0'],
            'pelanggan_id' => ['nullable', 'exists:pelanggan,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.produk_id' => ['required', 'exists:produk,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }
}
