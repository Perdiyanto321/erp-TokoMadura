<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BayarUtangRequest extends FormRequest
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
            'jumlah' => ['required', 'numeric', 'min:1'],
            'metode' => ['required', 'string'],
            'tanggal' => ['nullable', 'date'],
        ];
    }
}
