<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClientRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'          => ['sometimes', 'string', 'max:100'],
            'prenom'       => ['sometimes', 'nullable', 'string', 'max:100'],
            'telephone'    => ['sometimes', 'nullable', 'string', 'max:20'],
            'type_profil'  => ['sometimes', 'nullable', 'string', 'in:homme,femme,enfant,mixte'],
            'avatar_index' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:6'],
            'is_vip'       => ['sometimes', 'boolean'],
            'notes'        => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
