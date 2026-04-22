<?php

namespace Database\Seeders;

use App\Models\Vetement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VetementsSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'template_numero'  => 1,
                'nom'              => 'Robe simple',
                'libelles_mesures' => ['Longueur totale', 'Tour de poitrine', 'Tour de taille', 'Tour de hanches', 'Longueur épaule', 'Largeur épaule'],
            ],
            [
                'template_numero'  => 2,
                'nom'              => 'Robe de cérémonie',
                'libelles_mesures' => ['Longueur totale', 'Tour de poitrine', 'Tour de taille', 'Tour de hanches', 'Longueur épaule', 'Largeur épaule', 'Longueur manche', 'Tour de bras'],
            ],
            [
                'template_numero'  => 3,
                'nom'              => 'Boubou femme',
                'libelles_mesures' => ['Longueur totale', 'Tour de poitrine', 'Tour de taille', 'Tour de hanches', 'Largeur épaule', 'Longueur manche'],
            ],
            [
                'template_numero'  => 4,
                'nom'              => 'Jupe droite',
                'libelles_mesures' => ['Longueur jupe', 'Tour de taille', 'Tour de hanches', 'Hauteur bassin'],
            ],
            [
                'template_numero'  => 5,
                'nom'              => 'Jupe évasée / wax',
                'libelles_mesures' => ['Longueur jupe', 'Tour de taille', 'Tour de hanches', 'Hauteur bassin'],
            ],
            [
                'template_numero'  => 6,
                'nom'              => 'Haut / Blouse femme',
                'libelles_mesures' => ['Longueur dos', 'Tour de poitrine', 'Tour de taille', 'Largeur épaule', 'Longueur manche', 'Tour de bras', 'Tour de poignet'],
            ],
            [
                'template_numero'  => 7,
                'nom'              => 'Ensemble 2 pièces femme',
                'libelles_mesures' => ['Longueur haut', 'Longueur bas', 'Tour de poitrine', 'Tour de taille', 'Tour de hanches', 'Largeur épaule', 'Longueur manche'],
            ],
            [
                'template_numero'  => 8,
                'nom'              => 'Combinaison / Jumpsuit',
                'libelles_mesures' => ['Longueur totale', 'Tour de poitrine', 'Tour de taille', 'Tour de hanches', 'Entrejambe', 'Largeur épaule', 'Longueur manche'],
            ],
            [
                'template_numero'  => 9,
                'nom'              => 'Boubou homme',
                'libelles_mesures' => ['Longueur totale', 'Tour de poitrine', 'Tour de taille', 'Largeur épaule', 'Longueur manche', 'Tour de cou'],
            ],
            [
                'template_numero'  => 10,
                'nom'              => 'Chemise homme',
                'libelles_mesures' => ['Longueur dos', 'Tour de poitrine', 'Tour de taille', 'Largeur épaule', 'Longueur manche', 'Tour de poignet', 'Tour de cou'],
            ],
            [
                'template_numero'  => 11,
                'nom'              => 'Pantalon homme',
                'libelles_mesures' => ['Longueur totale', 'Tour de taille', 'Tour de hanches', 'Entrejambe', 'Tour de cuisse', 'Tour de genou', 'Tour de bas de jambe'],
            ],
            [
                'template_numero'  => 12,
                'nom'              => 'Costume / Complet veston',
                'libelles_mesures' => ['Longueur veste', 'Tour de poitrine', 'Tour de taille', 'Tour de hanches', 'Largeur épaule', 'Longueur manche', 'Longueur pantalon', 'Entrejambe'],
            ],
            [
                'template_numero'  => 13,
                'nom'              => 'Kaftan homme',
                'libelles_mesures' => ['Longueur totale', 'Tour de poitrine', 'Tour de taille', 'Tour de hanches', 'Largeur épaule', 'Longueur manche', 'Tour de cou'],
            ],
            [
                'template_numero'  => 14,
                'nom'              => 'Ensemble homme 2 pièces',
                'libelles_mesures' => ['Longueur haut', 'Longueur pantalon', 'Tour de poitrine', 'Tour de taille', 'Entrejambe', 'Largeur épaule'],
            ],
            [
                'template_numero'  => 15,
                'nom'              => 'Robe fillette',
                'libelles_mesures' => ['Longueur totale', 'Tour de poitrine', 'Tour de taille', 'Tour de hanches', 'Largeur épaule'],
            ],
            [
                'template_numero'  => 16,
                'nom'              => 'Ensemble enfant',
                'libelles_mesures' => ['Longueur haut', 'Longueur bas', 'Tour de poitrine', 'Tour de taille', 'Largeur épaule'],
            ],
            [
                'template_numero'  => 17,
                'nom'              => 'Tenue de baptême / cérémonie enfant',
                'libelles_mesures' => ['Longueur totale', 'Tour de poitrine', 'Tour de taille', 'Largeur épaule', 'Longueur manche'],
            ],
            [
                'template_numero'  => 18,
                'nom'              => 'Pantalon enfant',
                'libelles_mesures' => ['Longueur totale', 'Tour de taille', 'Tour de hanches', 'Entrejambe'],
            ],
            [
                'template_numero'  => 19,
                'nom'              => 'Uniforme scolaire',
                'libelles_mesures' => ['Longueur haut', 'Longueur jupe/pantalon', 'Tour de poitrine', 'Tour de taille', 'Largeur épaule'],
            ],
            [
                'template_numero'  => 20,
                'nom'              => 'Tenue personnalisée (libre)',
                'libelles_mesures' => ['Mesure 1', 'Mesure 2', 'Mesure 3', 'Mesure 4', 'Mesure 5'],
            ],
        ];

        foreach ($templates as $template) {
            Vetement::updateOrCreate(
                ['template_numero' => $template['template_numero'], 'is_systeme' => true],
                [
                    'id'               => Str::uuid(),
                    'atelier_id'       => null,
                    'nom'              => $template['nom'],
                    'libelles_mesures' => json_encode($template['libelles_mesures']),
                    'template_numero'  => $template['template_numero'],
                    'is_systeme'       => true,
                    'is_archived'      => false,
                ]
            );
        }

        $this->command->info('✅ VetementsSeeder : 20 templates système insérés');
    }
}
