<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVetementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'                => ['sometimes', 'string', 'max:100'],
            'libelles_mesures'   => ['sometimes', 'array'],
            'libelles_mesures.*' => ['string', 'max:100'],
        ];
    }
}
