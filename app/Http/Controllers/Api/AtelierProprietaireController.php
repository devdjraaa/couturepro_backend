<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\NiveauConfig;
use App\Models\NotificationSysteme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AtelierProprietaireController extends Controller
{
    /**
     * Liste tous les ateliers du propriétaire connecté (maître + sous-ateliers).
     */
    public function mesAteliers(Request $request): JsonResponse
    {
        $proprietaire = $request->user();

        $ateliers = Atelier::where('proprietaire_id', $proprietaire->id)
            ->with('abonnement')
            ->withCount(['clients', 'commandes'])
            ->orderByDesc('is_maitre')
            ->get()
            ->map(fn ($a) => [
                'id'       => $a->id,
                'nom'      => $a->nom,
                'ville'    => $a->ville,
                'is_maitre'=> $a->is_maitre,
                'statut'   => $a->statut,
                'clients_count'   => $a->clients_count,
                'commandes_count' => $a->commandes_count,
                'abonnement' => $a->abonnement ? [
                    'statut'          => $a->abonnement->statut,
                    'niveau_cle'      => $a->abonnement->niveau_cle,
                    'jours_restants'  => $a->abonnement->jours_restants,
                ] : null,
            ]);

        return response()->json($ateliers);
    }

    /**
     * Crée un sous-atelier avec 14 jours d'essai automatique.
     */
    public function store(Request $request): JsonResponse
    {
        $proprietaire = $request->user();

        // Limite basée sur le plan du maître (configurable depuis l'admin)
        $maitre = Atelier::where('proprietaire_id', $proprietaire->id)
            ->where('is_maitre', true)
            ->with('abonnement.niveau')
            ->first();

        $planConfig      = $maitre?->abonnement?->getConfigEffective() ?? [];
        $maxSousAteliers = $planConfig['max_sous_ateliers'] ?? 0;

        $countSous = Atelier::where('proprietaire_id', $proprietaire->id)
            ->where('is_maitre', false)
            ->count();

        if ($maxSousAteliers === 0) {
            return response()->json(['message' => 'Votre plan ne permet pas de créer des sous-ateliers.'], 403);
        }
        if ($countSous >= $maxSousAteliers) {
            return response()->json(['message' => "Nombre maximum de sous-ateliers atteint ({$maxSousAteliers})."], 403);
        }

        $data = $request->validate([
            'nom'    => 'required|string|max:150',
            'ville'  => 'nullable|string|max:100',
            'adresse'=> 'nullable|string|max:255',
        ]);

        $atelier = Atelier::create([
            'proprietaire_id' => $proprietaire->id,
            'nom'             => $data['nom'],
            'ville'           => $data['ville'] ?? null,
            'adresse'         => $data['adresse'] ?? null,
            // Un sous-atelier hérite du type du compte (le multi-ateliers est réservé aux designers).
            'type'            => $proprietaire->type_atelier ?: 'artisan',
            'is_maitre'       => false,
            'statut'          => 'actif',
            'essai_expire_at' => now()->addDays(14),
        ]);

        $cleEssai    = NiveauConfig::cleEssaiPour($atelier->type);
        $niveauEssai = NiveauConfig::where('cle', $cleEssai)->first();

        Abonnement::create([
            'atelier_id'           => $atelier->id,
            'niveau_cle'           => $niveauEssai?->cle ?? $cleEssai,
            'statut'               => 'essai',
            'jours_restants'       => 14,
            'timestamp_debut'      => now(),
            'timestamp_expiration' => now()->addDays(14),
            'config_snapshot'      => $niveauEssai?->config,
        ]);

        return response()->json([
            'id'        => $atelier->id,
            'nom'       => $atelier->nom,
            'ville'     => $atelier->ville,
            'is_maitre' => $atelier->is_maitre,
            'statut'    => $atelier->statut,
        ], 201);
    }

    /**
     * Stats résumées pour un sous-atelier (vue consolidée du maître).
     */
    public function stats(Request $request, string $atelierIdParam): JsonResponse
    {
        $proprietaire = $request->user();

        $atelier = Atelier::where('id', $atelierIdParam)
            ->where('proprietaire_id', $proprietaire->id)
            ->withCount(['clients', 'commandes'])
            ->firstOrFail();

        $commandesEnCours = $atelier->commandes()->where('statut', 'en_cours')->count();
        $commandesRetard  = $atelier->commandes()
            ->where('statut', 'en_cours')
            ->whereNotNull('date_livraison')
            ->where('date_livraison', '<', now())
            ->count();

        return response()->json([
            'id'               => $atelier->id,
            'nom'              => $atelier->nom,
            'is_maitre'        => $atelier->is_maitre,
            'clients_count'    => $atelier->clients_count,
            'commandes_count'  => $atelier->commandes_count,
            'commandes_en_cours' => $commandesEnCours,
            'commandes_retard'   => $commandesRetard,
        ]);
    }

    // #48 — Répercuter config sur tous les sous-ateliers depuis le maître
    // Appelé automatiquement après activerCode, ou manuellement via POST /ateliers/sync-config
    public function syncConfig(Request $request): JsonResponse
    {
        $proprietaire = $request->user();
        $maitre       = Atelier::where('proprietaire_id', $proprietaire->id)
            ->where('is_maitre', true)
            ->with('abonnement.niveau')
            ->firstOrFail();

        $configMaitre = $maitre->abonnement?->getConfigEffective() ?? [];
        if (empty($configMaitre)) {
            return response()->json(['message' => 'Aucune config active sur l\'atelier maître.'], 422);
        }

        $sousAteliers = Atelier::where('proprietaire_id', $proprietaire->id)
            ->where('is_maitre', false)
            ->with('abonnement')
            ->get();

        foreach ($sousAteliers as $sousAtelier) {
            if ($sousAtelier->abonnement) {
                $sousAtelier->abonnement->update(['config_snapshot' => $configMaitre]);
            }
        }

        return response()->json(['message' => count($sousAteliers) . ' sous-atelier(s) synchronisé(s).']);
    }

    // #54-55 — Vérifier et verrouiller les ateliers excédentaires lors d'un downgrade
    public function downgradeCheck(Request $request): JsonResponse
    {
        $proprietaire = $request->user();
        $maitre       = Atelier::where('proprietaire_id', $proprietaire->id)
            ->where('is_maitre', true)
            ->with('abonnement')
            ->firstOrFail();

        $config         = $maitre->abonnement?->getConfigEffective() ?? [];
        $maxSousAteliers = (int) ($config['max_sous_ateliers'] ?? 0);

        $sousAteliers = Atelier::where('proprietaire_id', $proprietaire->id)
            ->where('is_maitre', false)
            ->orderBy('created_at')
            ->get();

        $excedentaires = $sousAteliers->skip($maxSousAteliers);
        $locked        = [];

        foreach ($excedentaires as $atelier) {
            $atelier->update(['statut' => 'verrouille']);
            NotificationSysteme::create([
                'atelier_id' => $maitre->id,
                'titre'      => "Atelier verrouillé : {$atelier->nom}",
                'contenu'    => "Votre plan actuel permet {$maxSousAteliers} sous-atelier(s). L'atelier \"{$atelier->nom}\" a été verrouillé. Mettez à niveau votre plan pour y accéder à nouveau.",
                'type'       => 'atelier_verrouille',
                'is_read'    => false,
            ]);
            $locked[] = ['id' => $atelier->id, 'nom' => $atelier->nom];
        }

        return response()->json([
            'plan_max_sous_ateliers' => $maxSousAteliers,
            'total_sous_ateliers'    => $sousAteliers->count(),
            'verrouilles'            => $locked,
            'message'                => count($locked) > 0
                ? count($locked) . ' atelier(s) verrouillé(s) suite au changement de plan.'
                : 'Aucun atelier excédentaire.',
        ]);
    }

    // Déverrouiller un sous-atelier (après mise à niveau du plan)
    public function deverrouiller(Request $request, string $atelierIdParam): JsonResponse
    {
        $proprietaire = $request->user();
        $atelier      = Atelier::where('id', $atelierIdParam)
            ->where('proprietaire_id', $proprietaire->id)
            ->where('is_maitre', false)
            ->firstOrFail();

        $maitre  = Atelier::where('proprietaire_id', $proprietaire->id)->where('is_maitre', true)->with('abonnement')->first();
        $config  = $maitre?->abonnement?->getConfigEffective() ?? [];
        $max     = (int) ($config['max_sous_ateliers'] ?? 0);
        $actifs  = Atelier::where('proprietaire_id', $proprietaire->id)
            ->where('is_maitre', false)
            ->where('statut', 'actif')
            ->count();

        if ($actifs >= $max) {
            return response()->json([
                'message'    => "Votre plan permet {$max} sous-atelier(s) actif(s). Mettez à niveau pour en débloquer davantage.",
                'plan_requis'=> $max + 1 . ' sous-ateliers',
            ], 403);
        }

        $atelier->update(['statut' => 'actif']);

        return response()->json(['message' => "Atelier \"{$atelier->nom}\" déverrouillé."]);
    }
}
