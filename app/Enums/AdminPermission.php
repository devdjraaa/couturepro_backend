<?php

namespace App\Enums;

class AdminPermission
{
    const PERMISSIONS = [
        'ateliers.view'           => 'Voir tous les ateliers',
        'ateliers.freeze'         => 'Geler / dégeler un atelier',
        'ateliers.subscription'   => "Modifier l'abonnement d'un atelier",
        'paiements.view'          => 'Voir les paiements (historique, statuts)',
        'paiements.validate'      => 'Valider manuellement un paiement litigieux',
        'paiements.refund'        => 'Marquer un paiement comme remboursé',
        'transactions.view'       => 'Voir les transactions',
        'transactions.create'     => "Créer des codes d'activation manuels",
        'transactions.cancel'     => 'Annuler une transaction',
        'plans.view'              => "Voir les plans d'abonnement",
        'plans.edit'              => 'Modifier prix et fonctionnalités des plans',
        'plans.create'            => 'Créer de nouveaux plans',
        'offres.view'             => 'Voir les offres spéciales',
        'offres.create'           => 'Créer une offre spéciale',
        'tickets.view'            => 'Voir les tickets support',
        'tickets.respond'         => 'Répondre aux tickets',
        'tickets.assign'          => 'Assigner un ticket',
        'tickets.close'           => 'Fermer / rouvrir un ticket',
        'notifications.broadcast' => 'Envoyer une notification broadcast',
        'blacklist.manage'        => 'Gérer la liste noire',
        'audit.view'              => "Voir le journal d'audit",
        'stats.view'              => 'Voir les statistiques et rentabilité',
        'admins.manage'           => 'Gérer les comptes admins (super_admin seulement)',
    ];

    public static function all(): array
    {
        return array_keys(self::PERMISSIONS);
    }

    public static function isValid(string $permission): bool
    {
        return array_key_exists($permission, self::PERMISSIONS);
    }

    public static function label(string $permission): string
    {
        return self::PERMISSIONS[$permission] ?? $permission;
    }
}
