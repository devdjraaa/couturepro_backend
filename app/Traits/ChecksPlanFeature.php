<?php

namespace App\Traits;

use App\Models\Atelier;
use App\Models\Fonctionnalite;
use App\Models\NiveauConfig;
use Illuminate\Http\JsonResponse;

trait ChecksPlanFeature
{
    protected function planGate(Atelier $atelier, string $feature): ?JsonResponse
    {
        $config = $atelier->abonnement?->getConfigEffective() ?? [];
        if (! empty($config[$feature])) {
            return null; // accès autorisé
        }

        $requis = $this->planRequisPour($feature, $config);

        return response()->json([
            'message'           => 'Votre plan actuel ne permet pas d\'accéder à cette fonctionnalité.',
            'feature_bloquee'   => $feature,
            'feature_label'     => $requis['feature_label'],
            'plan_actuel'       => $atelier->abonnement?->niveau_cle ?? 'aucun',
            'plan_requis'       => $requis['cle'],
            'plan_requis_label' => $requis['label'],
            'avantages'         => $requis['avantages'],
            'action'            => 'upgrade',
        ], 403);
    }

    /**
     * S02A-28 — Plan requis pour dépasser une LIMITE numérique (quotas).
     *
     * Renvoie le plan ACTIF le moins cher dont la limite est supérieure à celle
     * atteinte (ou illimitée : `null` / `-1`). Évite d'écrire un plan en dur dans
     * chaque message de quota. `null` si aucun plan ne fait mieux.
     */
    protected function planRequisPourLimite(string $cle, ?int $valeurActuelle): ?array
    {
        $plan = NiveauConfig::where('is_actif', true)
            ->orderBy('prix_mensuel_equivalent_xof')
            ->orderBy('ordre_affichage')
            ->get()
            ->first(function (NiveauConfig $p) use ($cle, $valeurActuelle) {
                $v = ($p->config ?? [])[$cle] ?? null;
                if ($v === null || (int) $v === -1) {
                    return true;                                  // illimité
                }
                return $valeurActuelle === null || (int) $v > $valeurActuelle;
            });

        return $plan ? ['cle' => $plan->cle, 'label' => $plan->label] : null;
    }

    /**
     * S02A-28 — Plan requis = le plan ACTIF le moins cher qui active la fonctionnalité.
     *
     * Tout est dérivé de la base (`niveaux_config` + `fonctionnalites`) : plus aucun
     * plan ni libellé codé en dur. Auparavant une table PHP figée renvoyait vers des
     * plans LEGACY DÉSACTIVÉS (premium_mensuel, standard_annuel) et annonçait des
     * quotas faux (« 5 photos/mois », « jusqu'à 50 vidéos »). Un changement de grille
     * tarifaire est désormais suivi automatiquement.
     *
     * @param  array  $configActuelle  config du plan courant, pour ne proposer que le différentiel
     */
    private function planRequisPour(string $feature, array $configActuelle = []): array
    {
        $labels = Fonctionnalite::where('is_actif', true)->pluck('label', 'cle');
        $featureLabel = $labels[$feature] ?? $feature;

        $plan = NiveauConfig::where('is_actif', true)
            ->orderBy('prix_mensuel_equivalent_xof')
            ->orderBy('ordre_affichage')
            ->get()
            ->first(fn (NiveauConfig $p) => ! empty(($p->config ?? [])[$feature]));

        if (! $plan) {
            return [
                'cle'           => null,
                'label'         => 'Offre supérieure',
                'feature_label' => $featureLabel,
                'avantages'     => [],
            ];
        }

        // Avantages = ce que ce plan débloque EN PLUS du plan actuel (libellés en base).
        $avantages = array_filter([$plan->description_courte, $featureLabel]);
        foreach (($plan->config ?? []) as $cle => $valeur) {
            if (count($avantages) >= 4) {
                break;
            }
            if ($valeur !== true || $cle === $feature) {
                continue;                       // uniquement les fonctionnalités activées, hors celle demandée
            }
            if (! empty($configActuelle[$cle]) || ! isset($labels[$cle])) {
                continue;                       // déjà disponible, ou sans libellé public
            }
            $avantages[] = $labels[$cle];
        }

        return [
            'cle'           => $plan->cle,
            'label'         => $plan->label,
            'feature_label' => $featureLabel,
            'avantages'     => array_values($avantages),
        ];
    }
}
