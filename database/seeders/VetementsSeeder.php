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
            [ 'template_numero' => 1,  'nom' => 'Robe simple' ],
            [ 'template_numero' => 2,  'nom' => 'Robe de cérémonie' ],
            [ 'template_numero' => 3,  'nom' => 'Boubou femme' ],
            [ 'template_numero' => 4,  'nom' => 'Jupe droite' ],
            [ 'template_numero' => 5,  'nom' => 'Jupe évasée / wax' ],
            [ 'template_numero' => 6,  'nom' => 'Haut / Blouse femme' ],
            [ 'template_numero' => 7,  'nom' => 'Ensemble 2 pièces femme' ],
            [ 'template_numero' => 8,  'nom' => 'Combinaison / Jumpsuit' ],
            [ 'template_numero' => 9,  'nom' => 'Boubou homme' ],
            [ 'template_numero' => 10, 'nom' => 'Chemise homme' ],
            [ 'template_numero' => 11, 'nom' => 'Pantalon homme' ],
            [ 'template_numero' => 12, 'nom' => 'Costume / Complet veston' ],
            [ 'template_numero' => 13, 'nom' => 'Kaftan homme' ],
            [ 'template_numero' => 14, 'nom' => 'Ensemble homme 2 pièces' ],
            [ 'template_numero' => 15, 'nom' => 'Robe fillette' ],
            [ 'template_numero' => 16, 'nom' => 'Ensemble enfant' ],
            [ 'template_numero' => 17, 'nom' => 'Tenue de baptême / cérémonie enfant' ],
            [ 'template_numero' => 18, 'nom' => 'Pantalon enfant' ],
            [ 'template_numero' => 19, 'nom' => 'Uniforme scolaire' ],
            [ 'template_numero' => 20, 'nom' => 'Tenue personnalisée (libre)' ],
        ];

        foreach ($templates as $template) {
            Vetement::updateOrCreate(
                ['template_numero' => $template['template_numero'], 'is_systeme' => true],
                [
                    'atelier_id'      => null,
                    'nom'             => $template['nom'],
                    'template_numero' => $template['template_numero'],
                    'is_systeme'      => true,
                    'is_archived'     => false,
                ]
            );
        }

        $this->command->info('✅ VetementsSeeder : 20 templates système insérés');
    }
}
