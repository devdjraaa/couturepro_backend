<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RecuperationEtape1Request extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            // Au moins un des deux est requis : email OU téléphone
            'email'     => ['nullable', 'required_without:telephone', 'email'],
            'telephone' => ['nullable', 'required_without:email', 'string'],
        ];
    }
}
