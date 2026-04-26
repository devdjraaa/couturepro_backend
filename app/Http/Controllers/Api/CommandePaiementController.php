<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\Commande;
use App\Models\CommandePaiement;
use App\Models\EquipeMembre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandePaiementController extends Controller
{
    public function index(Request $request, Commande $commande): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($commande->atelier_id !== $atelier->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return response()->json($commande->commandePaiements()->orderByDesc('created_at')->get());
    }

    public function store(Request $request, Commande $commande): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($commande->atelier_id !== $atelier->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $data = $request->validate([
            'montant'       => ['required', 'numeric', 'min:1'],
            'mode_paiement' => ['required', 'in:especes,mobile_money,virement'],
        ]);

        $user = $request->user();

        $paiement = CommandePaiement::create([
            'commande_id'    => $commande->id,
            'atelier_id'     => $atelier->id,
            'montant'        => $data['montant'],
            'mode_paiement'  => $data['mode_paiement'],
            'enregistre_par' => $user->id,
        ]);

        // Met à jour le total des avances
        $commande->increment('acompte', $data['montant']);
        $commande->refresh();

        $whatsappUrl = null;
        $config = $atelier->abonnement?->getConfigEffective() ?? [];
        if (!empty($config['facture_whatsapp'])) {
            $client = $commande->client;
            if ($client?->telephone) {
                $reste    = max(0, (float) $commande->prix - (float) $commande->acompte);
                $modeLabel = match ($data['mode_paiement']) {
                    'mobile_money' => 'Mobile Money',
                    'virement'     => 'Virement',
                    default        => 'Espèces',
                };
                $msg = "Bonjour {$client->prenom},\n\n"
                     . "✅ Paiement reçu : *" . number_format((float) $data['montant'], 0, '.', ' ') . " FCFA*\n"
                     . "Mode : {$modeLabel}\n\n"
                     . "📋 Commande : #" . strtoupper(substr($commande->id, 0, 8)) . "\n"
                     . "💰 Total : " . number_format((float) $commande->prix, 0, '.', ' ') . " FCFA\n"
                     . "✅ Versé : " . number_format((float) $commande->acompte, 0, '.', ' ') . " FCFA\n";
                if ($reste > 0) {
                    $msg .= "⏳ Reste : " . number_format($reste, 0, '.', ' ') . " FCFA\n";
                } else {
                    $msg .= "🎉 Commande entièrement réglée !\n";
                }
                $msg .= "\nMerci pour votre confiance 🙏\n— {$atelier->nom}";

                $tel = preg_replace('/\D/', '', $client->telephone);
                $whatsappUrl = 'https://wa.me/' . $tel . '?text=' . urlencode($msg);
            }
        }

        return response()->json([
            'paiement'     => $paiement,
            'whatsapp_url' => $whatsappUrl,
        ], 201);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();

        return $user instanceof EquipeMembre
            ? $user->atelier
            : $user->atelierMaitre;
    }
}
