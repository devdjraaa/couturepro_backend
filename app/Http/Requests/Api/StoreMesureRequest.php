<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMesureRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id'   => ['required', 'uuid', 'exists:clients,id'],
            'vetement_id' => ['required', 'uuid', 'exists:vetements,id'],
            'champs'      => ['required', 'array'],
        ];
    }
}
