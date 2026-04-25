<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreVetementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'              => ['required', 'string', 'max:100'],
            'libelles_mesures'   => ['nullable', 'array'],
            'libelles_mesures.*' => ['string', 'max:100'],
        ];
    }
}
