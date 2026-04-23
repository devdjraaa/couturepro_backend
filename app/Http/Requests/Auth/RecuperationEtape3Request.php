<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RecuperationEtape3Request extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'demande_id'       => ['required', 'string', 'exists:demandes_recuperation,id'],
            'telephone_nouveau' => ['required', 'string', 'max:20'],
        ];
    }
}
