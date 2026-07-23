<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les noms des plans n'existaient qu'en francais.
 *
 * Ils viennent de la base, pas des fichiers de traduction : bascule en anglais,
 * la page affichait donc « Gratuit », « Atelier Mensuel », « Studio Annuel » —
 * et leurs descriptions en francais avec. Seuls les libelles de l'interface
 * etaient traduits, ce qui donnait une page a moitie anglaise.
 *
 * Les colonnes restent VIDES par defaut : tant qu'aucune traduction n'est
 * saisie, le francais est servi. Mieux vaut un nom en francais qu'un champ vide
 * sur une page de tarifs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('niveaux_config', function (Blueprint $table) {
            $table->string('label_en')->nullable()->after('label');
            $table->string('description_courte_en')->nullable()->after('description_courte');
        });
    }

    public function down(): void
    {
        Schema::table('niveaux_config', function (Blueprint $table) {
            $table->dropColumn(['label_en', 'description_courte_en']);
        });
    }
};
