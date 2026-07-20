<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVetementRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nom'       => ['sometimes', 'string', 'max:150'],
            'image'     => ['nullable', 'image', 'max:4096'],
            // Garde-fou absolu — le plafond réel vient du PLAN (max_photos_vetement).
            'images'    => ['nullable', 'array', 'max:20'],
            'images.*'  => ['image', 'max:4096'],
            // Pts 68-69 : mesures attendues pour ce type de vêtement (libellés
            // libres, ex. « tour_de_poitrine ») — proposées à la saisie en commande.
            'libelles_mesures'   => ['nullable', 'array', 'max:30'],
            'libelles_mesures.*' => ['string', 'max:60'],
        ];
    }
}
