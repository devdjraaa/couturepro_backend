<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommandeRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $prix    = (float) $this->input('prix', 0);
            $acompte = (float) $this->input('acompte', 0);

            if ($acompte > $prix && empty($this->input('motif_surplus_acompte'))) {
                $v->errors()->add(
                    'motif_surplus_acompte',
                    "L'acompte dépasse le montant total. Veuillez indiquer le motif."
                );
            }
        });
    }

    public function rules(): array
    {
        return [
            'client_id'             => ['required', 'uuid', 'exists:clients,id'],
            'vetement_id'           => ['required', 'uuid', 'exists:vetements,id'],
            'quantite'              => ['nullable', 'integer', 'min:1'],
            'prix'                  => ['required', 'numeric', 'min:0'],
            'acompte'               => ['nullable', 'numeric', 'min:0'],
            'motif_surplus_acompte' => ['nullable', 'string', 'max:500'],
            'date_livraison_prevue' => ['nullable', 'date', 'after_or_equal:today'],
            'note_interne'          => ['nullable', 'string', 'max:1000'],
            'description'           => ['nullable', 'string', 'max:2000'],
            'urgence'                => ['nullable', 'boolean'],
            'photo_tissu'            => ['nullable', 'image', 'max:8192'],
            'mode_paiement_acompte'  => ['nullable', 'in:especes,mobile_money,virement'],
        ];
    }
}
