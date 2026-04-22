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
            NiveauxConfigSeeder::class,    // 2. Plans (référence les clés de features par convention)
            VetementsSeeder::class,        // 3. Templates vêtements système (sans atelier_id)
            AdminSeeder::class,            // 4. Compte super_admin initial
        ]);
    }
}
