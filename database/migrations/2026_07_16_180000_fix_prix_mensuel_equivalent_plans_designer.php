<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// L'alignement des prix (migration 2026_07_16_140000) n'avait pas mis à jour
// prix_mensuel_equivalent_xof, resté aux anciens tarifs (1200/1000/2500/2083).
// Ce champ sert à l'affichage « /mois » des cartes ET au crédit prorata des
// upgrades (base 31 j) — valeurs officielles maquette : 2083 et 4167 pour les annuels.
return new class extends Migration
{
    private array $equivalents = [
        'free'            => 0,
        'atelier_mensuel' => 2500,
        'atelier_annuel'  => 2083,  // 25 000 / 12
        'master_mensuel'  => 5000,
        'master_annuel'   => 4167,  // 50 000 / 12
    ];

    public function up(): void
    {
        foreach ($this->equivalents as $cle => $equiv) {
            DB::table('niveaux_config')->where('cle', $cle)->update([
                'prix_mensuel_equivalent_xof' => $equiv,
                'updated_at'                  => now(),
            ]);
        }
    }

    public function down(): void
    {
        foreach (['free' => 0, 'atelier_mensuel' => 1200, 'atelier_annuel' => 1000,
                  'master_mensuel' => 2500, 'master_annuel' => 2083] as $cle => $equiv) {
            DB::table('niveaux_config')->where('cle', $cle)->update([
                'prix_mensuel_equivalent_xof' => $equiv,
                'updated_at'                  => now(),
            ]);
        }
    }
};
