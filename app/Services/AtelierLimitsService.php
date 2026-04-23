<?php

namespace App\Services;

use App\Models\Atelier;
use App\Models\QuotaMensuel;

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

        if ($max === null) {
            return true;
        }

        $quota = QuotaMensuel::courant($atelier->id);

        return $quota->nb_clients_crees < $max;
    }

    public function canCreateCommande(Atelier $atelier): bool
    {
        $abonnement = $atelier->abonnement;

        if (!$abonnement || $abonnement->statut === 'expire') {
            return false;
        }

        return true;
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
