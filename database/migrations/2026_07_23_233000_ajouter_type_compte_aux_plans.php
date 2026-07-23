<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rend explicite le public d'un plan d'abonnement.
 *
 * Jusqu'ici les plans étaient PARTAGÉS sans que rien ne le dise : la page de
 * tarifs proposait le même jeu de plans à un artisan et à un designer, et
 * l'administration n'indiquait nulle part lequel s'adressait à qui. D'où le
 * constat de la direction : « je ne vois pas les plans artisan ».
 *
 * `tous` par défaut sur l'existant : personne ne change de plan, aucun tarif ne
 * bouge. La direction crée ensuite ses plans artisan depuis l'administration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('niveaux_config', function (Blueprint $table) {
            // Chaîne plutôt qu'enum : ajouter un futur type ne demandera pas de
            // migration de type sur PostgreSQL.
            $table->string('type_compte', 20)->default('tous')->after('label');

            // Visibilité par surface. `is_actif` ne suffisait pas : il mélange
            // deux questions différentes — « ce plan existe-t-il encore ? » et
            // « où le montre-t-on ? ». On pouvait vouloir un plan réservé aux
            // comptes existants (invisible sur la vitrine mais toujours affiché
            // dans l'application), c'était impossible sans passer par le code.
            $table->boolean('visible_vitrine')->default(true)->after('is_actif');
            $table->boolean('visible_app')->default(true)->after('visible_vitrine');

            $table->index('type_compte');
        });
    }

    public function down(): void
    {
        Schema::table('niveaux_config', function (Blueprint $table) {
            $table->dropIndex(['type_compte']);
            $table->dropColumn(['type_compte', 'visible_vitrine', 'visible_app']);
        });
    }
};
