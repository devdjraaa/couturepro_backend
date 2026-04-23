<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'          => ['required', 'string', 'max:100'],
            'prenom'       => ['nullable', 'string', 'max:100'],
            'telephone'    => ['nullable', 'string', 'max:20'],
            'type_profil'  => ['nullable', 'string', 'in:vip,standard'],
        ];
    }
}
