<?php

namespace App\Traits;

use App\Models\Atelier;
use Illuminate\Http\JsonResponse;

trait ChecksPlanFeature
{
    protected function planGate(Atelier $atelier, string $feature): ?JsonResponse
    {
        $config = $atelier->abonnement?->getConfigEffective() ?? [];
        if (!empty($config[$feature])) {
            return null; // accès autorisé
        }

        $planRequis = $this->planRequisPour($feature);

        return response()->json([
            'message'         => 'Votre plan actuel ne permet pas d\'accéder à cette fonctionnalité.',
            'feature_bloquee' => $feature,
            'plan_actuel'     => $atelier->abonnement?->niveau_cle ?? 'aucun',
            'plan_requis'     => $planRequis['cle'],
            'plan_requis_label' => $planRequis['label'],
            'avantages'       => $planRequis['avantages'],
            'action'          => 'upgrade',
        ], 403);
    }

    private function planRequisPour(string $feature): array
    {
        $map = [
            'module_caisse'     => ['cle' => 'premium_mensuel', 'label' => 'Premium', 'avantages' => ['Module caisse complet', 'Factures WhatsApp', 'Multi-ateliers']],
            'facture_whatsapp'  => ['cle' => 'premium_mensuel', 'label' => 'Premium', 'avantages' => ['Factures WhatsApp illimitées', 'Module caisse']],
            'sauvegarde_auto'   => ['cle' => 'premium_annuel',  'label' => 'Premium Annuel', 'avantages' => ['Sauvegarde automatique', 'Statistiques avancées']],
            'multi_ateliers'    => ['cle' => 'standard_annuel', 'label' => 'Standard Annuel', 'avantages' => ['Jusqu\'à 1 sous-atelier', 'Gestion multi-sites']],
            'photos_vip'        => ['cle' => 'premium_mensuel', 'label' => 'Premium', 'avantages' => ['Galerie photos VIP', '5 photos/mois']],
            'facture_personnalisee' => ['cle' => 'premium_mensuel', 'label' => 'Premium', 'avantages' => ['Factures personnalisées avec logo', 'Références IFU / RCCM', 'Mise en page pro']],
        ];

        return $map[$feature] ?? ['cle' => 'premium_mensuel', 'label' => 'Premium ou supérieur', 'avantages' => ['Fonctionnalités avancées']];
    }
}
