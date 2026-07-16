<?php

namespace App\Http\Requests\Auth;

use App\Models\Proprietaire;
use Illuminate\Foundation\Http\FormRequest;

class InscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Le mutator du modèle normalise le téléphone à l'insert : la règle `unique`
    // doit donc tester la MÊME forme normalisée, sinon un doublon passe la
    // validation et explose en 500 (23505) à l'insert (vu en prod le 15/07).
    protected function prepareForValidation(): void
    {
        if ($this->filled('telephone')) {
            $this->merge(['telephone' => Proprietaire::normalizePhone($this->telephone)]);
        }
    }

    public function rules(): array
    {
        return [
            'nom'              => ['required', 'string', 'max:100'],
            'prenom'           => ['required', 'string', 'max:100'],
            'nom_atelier'      => ['required', 'string', 'max:150'],
            'type'             => ['sometimes', 'nullable', 'in:artisan,designer'],
            'telephone'        => ['required', 'string', 'max:20', 'unique:proprietaires,telephone'],
            'email'            => ['required', 'email', 'max:150', 'unique:proprietaires,email'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'question_secrete' => ['required', 'string', 'max:255'],
            'reponse_secrete'  => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.unique' => 'Ce numéro de téléphone est déjà associé à un compte. Connectez-vous ou utilisez « Récupérer mon compte ».',
            'email.unique'     => 'Cet e-mail est déjà associé à un compte. Connectez-vous ou utilisez « Récupérer mon compte ».',
        ];
    }
}
