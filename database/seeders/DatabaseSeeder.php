<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Ordre obligatoire — respecte les dépendances entre seeders
     */
    public function run(): void
    {
        $this->call([
            FonctionnalitesSeeder::class,  // 1. Catalogue des features (sans FK)
            NewFeaturesSeeder::class,      // 2. Nouvelles features (sprint avril 2026)
            NiveauxConfigSeeder::class,    // 3. Plans (référence les clés de features par convention)
            VetementsSeeder::class,        // 4. Templates vêtements système (sans atelier_id)
            AdminSeeder::class,            // 5. Compte super_admin initial
        ]);
    }
}
