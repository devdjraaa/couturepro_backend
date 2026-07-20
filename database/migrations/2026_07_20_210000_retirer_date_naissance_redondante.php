<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire `proprietaires.date_naissance`, ajoutée par erreur quelques heures plus
 * tôt : `naissance_jour` et `naissance_mois` existaient DÉJÀ et sont la source
 * utilisée par le formulaire comme par l'API. Garder les deux aurait créé la
 * divergence habituelle — un écran écrivant ici, un autre là.
 *
 * La colonne n'a jamais reçu de donnée (aucun code ne l'écrivait) : sa
 * suppression ne perd rien.
 *
 * `pseudo`, ajouté par la même migration, est CONSERVÉ : il répond à une
 * demande distincte de la direction et n'a pas d'équivalent existant.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('proprietaires', 'date_naissance')) {
            Schema::table('proprietaires', function (Blueprint $table) {
                $table->dropColumn('date_naissance');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('proprietaires', 'date_naissance')) {
            Schema::table('proprietaires', function (Blueprint $table) {
                $table->date('date_naissance')->nullable();
            });
        }
    }
};
