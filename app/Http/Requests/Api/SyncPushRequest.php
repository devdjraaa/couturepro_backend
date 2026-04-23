<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SyncPushRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'operations'              => ['required', 'array', 'max:20'],
            'operations.*.table'      => ['required', 'string', 'in:clients,commandes,mesures,vetements'],
            'operations.*.operation'  => ['required', 'string', 'in:create,update,delete'],
            'operations.*.id'         => ['required', 'string'],
            'operations.*.data'       => ['nullable', 'array'],
        ];
    }
}
