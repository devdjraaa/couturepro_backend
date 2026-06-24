<?php

use Database\Seeders\NiveauxConfigSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    // Applique la refonte des plans (anciens désactivés + nouveaux Free/Atelier/Master)
    // sur les bases existantes au déploiement. Les plans restent éditables en admin ensuite.
    public function up(): void
    {
        (new NiveauxConfigSeeder())->run();
    }

    public function down(): void
    {
        // Réversibilité non destructive : on réactive les anciens plans.
        \App\Models\NiveauConfig::whereIn('cle', [
            'standard_mensuel', 'standard_annuel',
            'premium_mensuel', 'premium_annuel',
            'magnat_mensuel', 'magnat_annuel',
        ])->update(['is_actif' => true]);
    }
};
