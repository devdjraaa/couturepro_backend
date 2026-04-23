<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class InscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom'              => ['required', 'string', 'max:100'],
            'prenom'           => ['required', 'string', 'max:100'],
            'telephone'        => ['required', 'string', 'max:20', 'unique:proprietaires,telephone'],
            'email'            => ['required', 'email', 'max:150', 'unique:proprietaires,email'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'question_secrete' => ['required', 'string', 'max:255'],
            'reponse_secrete'  => ['required', 'string', 'max:255'],
        ];
    }
}
