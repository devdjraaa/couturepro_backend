<?php

namespace Database\Seeders;

use App\Models\Fonctionnalite;
use Illuminate\Database\Seeder;

/**
 * Fonctionnalités ajoutées lors du sprint d'avril 2026
 * (feature gating, PDF export, WhatsApp rappels)
 */
class NewFeaturesSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            [
                'cle'             => 'export_pdf',
                'label'           => 'Export PDF mesures',
                'description'     => 'Télécharger les mesures d\'un client en format PDF',
                'type'            => 'booleen',
                'unite'           => null,
                'categorie'       => 'clients_commandes',
                'valeur_defaut'   => 'true',
                'ordre_affichage' => 15,
            ],
            [
                'cle'             => 'rappels_whatsapp_auto',
                'label'           => 'Rappels WhatsApp automatiques',
                'description'     => 'Envoi de messages WhatsApp à la création commande, J-2, commande prête',
                'type'            => 'booleen',
                'unite'           => null,
                'categorie'       => 'communication',
                'valeur_defaut'   => 'true',
                'ordre_affichage' => 16,
            ],
        ];

        foreach ($features as $feature) {
            Fonctionnalite::updateOrCreate(['cle' => $feature['cle']], $feature);
        }

        $this->command->info('✅ NewFeaturesSeeder : ' . count($features) . ' features insérées');
    }
}
