<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCommandeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vetement_id'              => ['sometimes', 'uuid', 'exists:vetements,id'],
            'quantite'                 => ['sometimes', 'integer', 'min:1'],
            'prix'                     => ['sometimes', 'numeric', 'min:0'],
            'acompte'                  => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'statut'                   => ['sometimes', 'string', 'in:en_cours,livre,annule'],
            'date_livraison_prevue'    => ['sometimes', 'nullable', 'date'],
            'date_livraison_effective' => ['sometimes', 'nullable', 'date'],
            'note_interne'             => ['sometimes', 'nullable', 'string', 'max:1000'],
            'description'              => ['sometimes', 'nullable', 'string', 'max:2000'],
            'urgence'                  => ['sometimes', 'nullable', 'boolean'],
            'photo_tissu'              => ['sometimes', 'nullable', 'image', 'max:8192'],
        ];
    }
}
