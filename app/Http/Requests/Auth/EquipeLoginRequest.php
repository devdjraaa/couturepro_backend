<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class EquipeLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code_acces' => ['required', 'string'],
            'password'   => ['required', 'string'],
            'device_id'  => ['required', 'string', 'max:255'],
        ];
    }
}
