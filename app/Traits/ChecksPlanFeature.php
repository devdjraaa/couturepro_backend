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
            'export_groupe'     => ['cle' => 'atelier_mensuel', 'label' => 'Atelier', 'avantages' => ['Export groupé des mesures', 'Exports groupés collections et patrons (PDF)']],
            'lookbook_pdf'      => ['cle' => 'atelier_mensuel', 'label' => 'Atelier', 'avantages' => ['Lookbook PDF de vos collections', 'Catalogue partageable']],
            'rapport_mensuel'   => ['cle' => 'atelier_mensuel', 'label' => 'Atelier', 'avantages' => ['Rapport PDF mensuel', 'Suivi encaissements par cliente']],
            'liste_attente'     => ['cle' => 'master_mensuel', 'label' => 'Studio', 'avantages' => ['Liste d\'attente clients', 'Gestion de la demande']],
            'simulateur_revenus' => ['cle' => 'master_mensuel', 'label' => 'Studio', 'avantages' => ['Simulateur de revenus', 'Projections d\'activité']],
            'annonce_collection' => ['cle' => 'master_mensuel', 'label' => 'Studio', 'avantages' => ['Annonce de collection', 'Mise en avant vitrine']],
            'videos_presentation' => ['cle' => 'master_mensuel', 'label' => 'Studio', 'avantages' => ['Jusqu\'à 50 vidéos de présentation', 'Vitrine enrichie']],
            'badge_designer_pro' => ['cle' => 'atelier_mensuel', 'label' => 'Atelier', 'avantages' => ['Badge Designer Pro', 'Crédibilité renforcée']],
            'fidelite_avancee'  => ['cle' => 'master_mensuel', 'label' => 'Studio', 'avantages' => ['Programme de fidélité avancé', 'Paliers et récompenses']],
            'backup_cloud'      => ['cle' => 'atelier_mensuel', 'label' => 'Atelier', 'avantages' => ['Sauvegarde cloud automatique', 'Données protégées']],
        ];

        return $map[$feature] ?? ['cle' => 'premium_mensuel', 'label' => 'Premium ou supérieur', 'avantages' => ['Fonctionnalités avancées']];
    }
}
