<?php

namespace Database\Seeders;

use App\Models\Fonctionnalite;
use Illuminate\Database\Seeder;

class FonctionnalitesSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [
                'cle'             => 'max_assistants',
                'label'           => 'Assistants max',
                'description'     => 'Nombre maximum de comptes assistants actifs',
                'type'            => 'numerique',
                'unite'           => 'comptes',
                'categorie'       => 'equipe',
                'valeur_defaut'   => '0',
                'ordre_affichage' => 1,
            ],
            [
                'cle'             => 'max_membres',
                'label'           => 'Membres / Lecteurs max',
                'description'     => 'Nombre maximum de membres lecteurs',
                'type'            => 'numerique',
                'unite'           => 'comptes',
                'categorie'       => 'equipe',
                'valeur_defaut'   => '0',
                'ordre_affichage' => 2,
            ],
            [
                'cle'             => 'max_clients_par_mois',
                'label'           => 'Clients & Commandes / mois',
                'description'     => 'Quota mensuel de créations clients ET commandes cumulées',
                'type'            => 'numerique',
                'unite'           => '/mois',
                'categorie'       => 'clients_commandes',
                'valeur_defaut'   => '50',
                'ordre_affichage' => 3,
            ],
            [
                'cle'             => 'photos_vip',
                'label'           => 'Album Photos VIP',
                'description'     => 'Accès à la galerie de photos modèles sur Cloudflare R2',
                'type'            => 'booleen',
                'unite'           => null,
                'categorie'       => 'stockage',
                'valeur_defaut'   => 'false',
                'ordre_affichage' => 4,
            ],
            [
                'cle'             => 'max_photos_vip_par_mois',
                'label'           => 'Photos VIP max / mois',
                'description'     => 'Nombre maximum de photos uploadées par mois (null = illimité)',
                'type'            => 'numerique',
                'unite'           => '/mois',
                'categorie'       => 'stockage',
                'valeur_defaut'   => '0',
                'ordre_affichage' => 5,
            ],
            [
                'cle'             => 'facture_whatsapp',
                'label'           => 'Envoi facture WhatsApp',
                'description'     => 'Génération et envoi de facture par WhatsApp',
                'type'            => 'booleen',
                'unite'           => null,
                'categorie'       => 'communication',
                'valeur_defaut'   => 'false',
                'ordre_affichage' => 6,
            ],
            [
                'cle'             => 'max_factures_par_mois',
                'label'           => 'Factures max / mois',
                'description'     => 'Quota mensuel de factures envoyées (null = illimité)',
                'type'            => 'numerique',
                'unite'           => '/mois',
                'categorie'       => 'communication',
                'valeur_defaut'   => '0',
                'ordre_affichage' => 7,
            ],
            [
                'cle'             => 'sauvegarde_auto',
                'label'           => 'Sauvegarde automatique',
                'description'     => 'Synchronisation automatique des données en arrière-plan',
                'type'            => 'booleen',
                'unite'           => null,
                'categorie'       => 'module',
                'valeur_defaut'   => 'false',
                'ordre_affichage' => 8,
            ],
            [
                'cle'             => 'module_caisse',
                'label'           => 'Module Caisse (Phase 2)',
                'description'     => 'Gestion des encaissements et suivi financier',
                'type'            => 'booleen',
                'unite'           => null,
                'categorie'       => 'module',
                'valeur_defaut'   => 'false',
                'ordre_affichage' => 9,
            ],
            [
                'cle'             => 'multi_ateliers',
                'label'           => 'Mode Multi-Ateliers',
                'description'     => "Gérer jusqu'à 7 ateliers depuis un seul compte",
                'type'            => 'booleen',
                'unite'           => null,
                'categorie'       => 'module',
                'valeur_defaut'   => 'false',
                'ordre_affichage' => 10,
            ],
            [
                'cle'             => 'max_sous_ateliers',
                'label'           => 'Sous-ateliers max',
                'description'     => 'Nombre maximum de sous-ateliers autorisés (0 = désactivé)',
                'type'            => 'numerique',
                'unite'           => 'ateliers',
                'categorie'       => 'module',
                'valeur_defaut'   => '0',
                'ordre_affichage' => 11,
            ],
            [
                'cle'             => 'pts_par_client',
                'label'           => 'Points par client créé',
                'description'     => "Points de fidélité gagnés à chaque création de client",
                'type'            => 'points',
                'unite'           => 'pts/client',
                'categorie'       => 'fidelite',
                'valeur_defaut'   => '1',
                'ordre_affichage' => 11,
            ],
            [
                'cle'             => 'pts_par_commande',
                'label'           => 'Points par commande validée',
                'description'     => "Points de fidélité gagnés à chaque commande livrée",
                'type'            => 'points',
                'unite'           => 'pts/commande',
                'categorie'       => 'fidelite',
                'valeur_defaut'   => '1',
                'ordre_affichage' => 12,
            ],
            [
                'cle'             => 'pts_activation',
                'label'           => "Points à l'activation",
                'description'     => "Bonus de points offert à chaque activation d'abonnement",
                'type'            => 'points',
                'unite'           => 'pts',
                'categorie'       => 'fidelite',
                'valeur_defaut'   => '31',
                'ordre_affichage' => 13,
            ],
            [
                'cle'             => 'seuil_conversion_pts',
                'label'           => 'Seuil conversion points→bonus',
                'description'     => 'Nombre de points nécessaires pour obtenir 1 jour de bonus',
                'type'            => 'points',
                'unite'           => 'pts',
                'categorie'       => 'fidelite',
                'valeur_defaut'   => '10000',
                'ordre_affichage' => 14,
            ],
        ];

        foreach ($features as $feature) {
            Fonctionnalite::updateOrCreate(['cle' => $feature['cle']], $feature);
        }

        $this->command->info('✅ FonctionnalitesSeeder : 15 features insérées');
    }
}
