<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommandeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'client_id'             => ['required', 'uuid', 'exists:clients,id'],
            'vetement_id'           => ['required', 'uuid', 'exists:vetements,id'],
            'quantite'              => ['nullable', 'integer', 'min:1'],
            'prix'                  => ['required', 'numeric', 'min:0'],
            'acompte'               => ['nullable', 'numeric', 'min:0'],
            'date_livraison_prevue' => ['nullable', 'date'],
            'note_interne'          => ['nullable', 'string', 'max:1000'],
            'description'           => ['nullable', 'string', 'max:2000'],
            'urgence'                => ['nullable', 'boolean'],
            'photo_tissu'            => ['nullable', 'image', 'max:8192'],
            'mode_paiement_acompte'  => ['nullable', 'in:especes,mobile_money,virement'],
        ];
    }
}
