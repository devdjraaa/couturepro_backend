<?php

namespace App\Services;

use App\Models\Atelier;
use App\Models\QuotaMensuel;
use App\Models\Vetement;

class AtelierLimitsService
{
    public function getConfig(Atelier $atelier): array
    {
        return $atelier->abonnement?->getConfigEffective() ?? [];
    }

    public function canCreateClient(Atelier $atelier): bool
    {
        $config = $this->getConfig($atelier);
        $max = $config['max_clients_par_mois'] ?? null;

        if ($max === null || (int) $max === -1) {
            return true;
        }

        $quota = QuotaMensuel::courant($atelier->id);

        return $quota->nb_clients_crees < $max;
    }

    public function canCreateCommande(Atelier $atelier): bool
    {
        $abonnement = $atelier->abonnement;

        if (!$abonnement) {
            return false;
        }

        // P156 : un abonnement expiré ne bloque plus tout — l'utilisateur continue avec les
        // limites du plan GRATUIT (getConfigEffective retombe sur la config free quand expiré).
        $config = $abonnement->getConfigEffective();
        $max = $config['max_commandes_par_mois'] ?? null;

        if ($max === null || (int) $max === -1) {
            return true;
        }

        return QuotaMensuel::courant($atelier->id)->nb_commandes_creees < $max;
    }

    public function canPublishVetement(Atelier $atelier): bool
    {
        $config = $this->getConfig($atelier);
        $max = $config['max_creations_vitrine'] ?? 10; // défaut offre gratuite

        if ((int) $max === -1) {
            return true;
        }

        $count = Vetement::where('atelier_id', $atelier->id)
            ->where('is_archived', false)
            ->where('publie_vitrine', true)
            ->count();

        return $count < $max;
    }

    public function incrementClients(Atelier $atelier): void
    {
        QuotaMensuel::courant($atelier->id)->increment('nb_clients_crees');
    }

    public function incrementCommandes(Atelier $atelier): void
    {
        QuotaMensuel::courant($atelier->id)->increment('nb_commandes_creees');
    }
}
