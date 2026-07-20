<?php

use App\Models\NiveauConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S02A-28 — Nombre de photos par élément, piloté par le plan.
 *
 * Les limites vivaient UNIQUEMENT dans le front (`MAX_IMAGES = 5` pour un modèle,
 * `MAX_PHOTOS = 6` pour une réalisation), identiques pour tous les plans. Deux
 * conséquences :
 *
 *  1. Impossible de différencier les formules sur ce critère sans redéploiement.
 *  2. Surtout : le SERVEUR n'imposait AUCUNE limite. Le front s'arrêtait à 5,
 *     mais l'API acceptait n'importe quel nombre d'images — n'importe quel appel
 *     direct pouvait remplir le stockage.
 *
 * ⚠️ Valeurs de départ = l'existant (5 et 6) pour le plan Gratuit, avec une marge
 * au-dessus. Personne ne perd donc de capacité. Les chiffres définitifs relèvent
 * de la direction et s'éditent en admin, comme les autres quotas.
 */
return new class extends Migration
{
    /** @return array{0:int,1:int} [photos par modèle, photos par réalisation] */
    private function quotasPour(string $cle): array
    {
        return match (true) {
            str_starts_with($cle, 'master_')  => [10, 12],  // Studio
            str_starts_with($cle, 'atelier_') => [8, 8],    // Atelier
            default                           => [5, 6],    // Gratuit et plans hérités = l'existant
        };
    }

    private array $fonctionnalites = [
        [
            'cle'         => 'max_photos_vetement',
            'label'       => 'Photos par modèle',
            'description' => 'Nombre de photos attachables à un modèle du catalogue',
            'ordre'       => 132,
        ],
        [
            'cle'         => 'max_photos_realisation',
            'label'       => 'Photos par réalisation',
            'description' => 'Nombre de photos attachables à une réalisation publiée',
            'ordre'       => 133,
        ],
    ];

    public function up(): void
    {
        foreach (NiveauConfig::all() as $plan) {
            [$vetement, $realisation] = $this->quotasPour($plan->cle);
            $config = $plan->config ?? [];
            $config['max_photos_vetement']    = $vetement;
            $config['max_photos_realisation'] = $realisation;
            $plan->update(['config' => $config]);
        }

        foreach ($this->fonctionnalites as $f) {
            if (DB::table('fonctionnalites')->where('cle', $f['cle'])->exists()) {
                continue;
            }
            DB::table('fonctionnalites')->insert([
                'cle'             => $f['cle'],
                'label'           => $f['label'],
                'description'     => $f['description'],
                'type'            => 'numerique',
                'unite'           => 'photos',
                'categorie'       => 'module',
                'is_actif'        => true,
                'ordre_affichage' => $f['ordre'],
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (NiveauConfig::all() as $plan) {
            $config = $plan->config ?? [];
            unset($config['max_photos_vetement'], $config['max_photos_realisation']);
            $plan->update(['config' => $config]);
        }

        DB::table('fonctionnalites')
            ->whereIn('cle', array_column($this->fonctionnalites, 'cle'))
            ->delete();
    }
};
