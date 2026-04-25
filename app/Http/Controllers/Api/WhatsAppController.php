<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\EquipeMembre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    public function rappelClient(Request $request, string $clientId): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $client = Client::where('id', $clientId)
            ->where('atelier_id', $atelier->id)
            ->firstOrFail();

        if (!$client->telephone) {
            return response()->json(['message' => 'Ce client n\'a pas de numéro de téléphone.'], 422);
        }

        $commande = Commande::where('client_id', $client->id)
            ->where('statut', 'en_cours')
            ->latest()
            ->first();

        $phone = preg_replace('/\D/', '', $client->telephone);

        if ($commande) {
            $message = "Bonjour {$client->prenom}, votre commande ({$commande->vetement_nom}) est en cours de préparation chez {$atelier->nom}. Merci de confirmer votre disponibilité pour la livraison.";
        } else {
            $message = "Bonjour {$client->prenom}, nous vous contactons depuis {$atelier->nom}. N'hésitez pas à nous rendre visite pour votre prochaine commande !";
        }

        $lien = 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);

        return response()->json(['lien' => $lien, 'message' => $message]);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();
        return $user instanceof EquipeMembre ? $user->atelier : $user->atelierMaitre;
    }
}
