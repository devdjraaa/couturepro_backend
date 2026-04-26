<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreVetementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'       => ['required', 'string', 'max:150'],
            'image'     => ['nullable', 'image', 'max:4096'],
            'images'    => ['nullable', 'array', 'max:5'],
            'images.*'  => ['image', 'max:4096'],
        ];
    }
}
