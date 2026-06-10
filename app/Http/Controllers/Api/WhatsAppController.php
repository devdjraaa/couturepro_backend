<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\CommandePaiement;
use App\Models\EquipeMembre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WhatsAppController extends Controller
{
    use ResolvesAtelier;
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

    public function confirmationCommande(Request $request, string $commandeId): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $commande = Commande::where('id', $commandeId)
            ->where('atelier_id', $atelier->id)
            ->with('client')
            ->firstOrFail();

        $client = $commande->client;

        if (!$client || !$client->telephone) {
            return response()->json(['message' => 'Ce client n\'a pas de numéro de téléphone.'], 422);
        }

        $phone   = preg_replace('/\D/', '', $client->telephone);
        $restant = max(0, ($commande->prix ?? 0) - ($commande->acompte ?? 0));

        $message = "Bonjour {$client->prenom}, votre commande ({$commande->vetement_nom}) a bien été enregistrée chez {$atelier->nom}.";
        if ($commande->acompte > 0) {
            $message .= ' Acompte reçu : ' . number_format($commande->acompte, 0, ',', ' ') . ' FCFA.';
        }
        if ($restant > 0) {
            $message .= ' Reste à payer : ' . number_format($restant, 0, ',', ' ') . ' FCFA.';
        }
        $message .= ' Merci de votre confiance !';

        $lien = 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);

        return response()->json(['lien' => $lien, 'message' => $message]);
    }

    public function commandePrete(Request $request, string $commandeId): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $commande = Commande::where('id', $commandeId)
            ->where('atelier_id', $atelier->id)
            ->with('client')
            ->firstOrFail();

        $client = $commande->client;

        if (!$client || !$client->telephone) {
            return response()->json(['message' => 'Ce client n\'a pas de numéro de téléphone.'], 422);
        }

        $phone   = preg_replace('/\D/', '', $client->telephone);
        $restant = max(0, ($commande->prix ?? 0) - ($commande->acompte ?? 0));

        $message = "Bonjour {$client->prenom}, votre commande ({$commande->vetement_nom}) est prête chez {$atelier->nom} !";
        if ($restant > 0) {
            $message .= ' Reste à payer : ' . number_format($restant, 0, ',', ' ') . ' FCFA.';
        } else {
            $message .= ' Tout a été réglé, venez récupérer votre commande.';
        }

        $lien = 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);

        return response()->json(['lien' => $lien, 'message' => $message]);
    }

    // #78-82 — Preuve de paiement / reçu complet par WhatsApp
    public function preuvePaiement(Request $request, string $commandeId): JsonResponse
    {
        $atelier  = $this->getAtelier($request);
        $commande = Commande::where('id', $commandeId)
            ->where('atelier_id', $atelier->id)
            ->with(['client', 'vetement', 'items', 'commandePaiements'])
            ->firstOrFail();

        $client = $commande->client;

        if (!$client || !$client->telephone) {
            return response()->json(['message' => 'Ce client n\'a pas de numéro de téléphone.'], 422);
        }

        $phone     = preg_replace('/\D/', '', $client->telephone);
        $nomClient = trim("{$client->prenom} {$client->nom}");
        $prix      = (float) $commande->prix;
        $acompte   = (float) ($commande->commandePaiements->sum('montant'));
        $restant   = max(0, $prix - $acompte);
        $date      = now()->format('d/m/Y');

        // Détail des items si disponibles, sinon vêtement principal
        $ligneItems = '';
        if ($commande->items->isNotEmpty()) {
            foreach ($commande->items as $item) {
                $nom    = $item->vetement_nom ?? $item->vetement?->nom ?? 'Article';
                $ligne  = "  • {$nom} × {$item->quantite} = " . number_format($item->quantite * $item->prix_unitaire, 0, ',', ' ') . ' FCFA';
                $ligneItems .= "\n" . $ligne;
            }
        } else {
            $nom = $commande->vetement_nom ?? $commande->vetement?->nom ?? 'Commande';
            $ligneItems = "\n  • {$nom} × {$commande->quantite}";
        }

        $lignes = [
            "🧾 *Reçu de paiement — {$atelier->nom}*",
            "📅 {$date}",
            '',
            "👤 Client : {$nomClient}",
            "📦 Commande :{$ligneItems}",
            '',
            '💰 Récapitulatif :',
            '  Total commande : ' . number_format($prix, 0, ',', ' ') . ' FCFA',
            '  Total encaissé : ' . number_format($acompte, 0, ',', ' ') . ' FCFA',
        ];

        if ($restant > 0) {
            $lignes[] = '  ⚠️ Reste à payer : ' . number_format($restant, 0, ',', ' ') . ' FCFA';
        } else {
            $lignes[] = '  ✅ Commande entièrement réglée';
        }

        $lignes[] = '';
        $lignes[] = '_Merci de votre confiance !_';

        $message = implode("\n", $lignes);
        $lien    = 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);

        return response()->json([
            'lien'    => $lien,
            'message' => $message,
            'resume'  => [
                'total'   => $prix,
                'paye'    => $acompte,
                'restant' => $restant,
            ],
        ]);
    }
}
