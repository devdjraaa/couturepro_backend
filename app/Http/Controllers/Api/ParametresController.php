<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Models\Atelier;
use App\Models\CommunicationsConfig;
use App\Models\EquipeMembre;
use App\Models\ParametresAtelier;
use App\Models\Proprietaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ParametresController extends Controller
{
    use ResolvesAtelier;
    public function updateProfil(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:255'],
            'telephone' => ['required', 'string', 'max:20'],
            'email'     => ['nullable', 'email', 'max:255'],
        ]);

        $user = $request->user();
        if (! $user instanceof Proprietaire) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $user->update($data);

        return response()->json([
            'nom'       => $user->nom,
            'telephone' => $user->telephone,
            'email'     => $user->email,
        ]);
    }

    public function updateAtelier(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'    => ['required', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville'  => ['nullable', 'string', 'max:100'],
        ]);

        $atelier = $this->getAtelier($request);
        $atelier->update($data);

        return response()->json([
            'nom'     => $atelier->nom,
            'adresse' => $atelier->adresse,
            'ville'   => $atelier->ville,
        ]);
    }

    public function getCommunications(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $config  = CommunicationsConfig::firstOrNew(['atelier_id' => $atelier->id]);

        return response()->json([
            'confirmation_commande' => (bool) $config->confirmation_commande,
            'rappel_livraison_j2'   => (bool) $config->rappel_livraison_j2,
            'commande_prete'        => (bool) $config->commande_prete,
            'whatsapp_enabled'      => (bool) $config->whatsapp_enabled,
        ]);
    }

    public function updateCommunications(Request $request): JsonResponse
    {
        $data = $request->validate([
            'confirmation_commande'  => ['boolean'],
            'rappel_livraison_j2'    => ['boolean'],
            'commande_prete'         => ['boolean'],
            'whatsapp_enabled'       => ['boolean'],
        ]);

        $atelier = $this->getAtelier($request);

        $config = CommunicationsConfig::updateOrCreate(
            ['atelier_id' => $atelier->id],
            $data
        );

        return response()->json([
            'confirmation_commande' => (bool) $config->confirmation_commande,
            'rappel_livraison_j2'   => (bool) $config->rappel_livraison_j2,
            'commande_prete'        => (bool) $config->commande_prete,
            'whatsapp_enabled'      => (bool) $config->whatsapp_enabled,
        ]);
    }

    public function changerMotDePasse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ancien'  => ['required', 'string'],
            'nouveau' => ['required', 'string', 'min:8'],
        ]);

        $user = $request->user();
        if (! $user instanceof Proprietaire) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        if (! Hash::check($data['ancien'], $user->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $user->update(['password' => $data['nouveau']]);

        return response()->json(['message' => 'Mot de passe modifié avec succès.']);
    }

    public function getPreferences(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $prefs   = ParametresAtelier::firstOrNew(['atelier_id' => $atelier->id]);

        return response()->json([
            'devise'       => $prefs->devise       ?? 'XOF',
            'unite_mesure' => $prefs->unite_mesure ?? 'cm',
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'devise'       => ['required', 'string', 'in:XOF,GNF,XAF,EUR,USD,GHS,NGN,MAD'],
            'unite_mesure' => ['required', 'string', 'in:cm,pouces'],
        ]);

        $atelier = $this->getAtelier($request);

        ParametresAtelier::updateOrCreate(
            ['atelier_id' => $atelier->id],
            $data
        );

        return response()->json($data);
    }

}
