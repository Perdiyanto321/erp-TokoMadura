<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProdukRequest extends FormRequest
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
        $id = $this->route('produk')?->id;

        return [
            'kategori_id' => ['required', 'exists:kategori,id'],
            'kode' => ['required', 'string', 'max:50', Rule::unique('produk', 'kode')->ignore($id)],
            'nama' => ['required', 'string', 'max:255'],
            'satuan' => ['required', 'string', 'max:50'],
            'satuan_beli' => ['required', 'string', 'max:50'],
            'isi_per_satuan_beli' => ['required', 'integer', 'min:1'],
            'harga_beli' => ['required', 'numeric', 'min:0'],
            'harga_jual' => ['required', 'numeric', 'min:0'],
            'stok' => ['nullable', 'integer', 'min:0'],
            'stok_minimum' => ['required', 'integer', 'min:0'],
            'stok_target' => ['required', 'integer', 'gte:stok_minimum'],
            'status' => ['nullable', 'in:aktif,nonaktif'],
        ];
    }
}
