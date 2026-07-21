<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `otp_tokens.type` : on retire l'énumération figée (CHECK Postgres / ENUM MySQL)
 * au profit d'une simple chaîne.
 *
 * POURQUOI — l'espace client génère ses codes avec le type « client_login », qui
 * n'avait jamais été ajouté à la contrainte. Résultat : toute demande de code
 * côté vitrine partait en 500 sur la prod (Postgres), alors que le local (MySQL,
 * ENUM plus permissif) laissait passer — invisible en dev, cassé en production.
 *
 * Ce n'est pas la première fois : chaque nouveau type d'OTP obligeait à une
 * migration ALTER CONSTRAINT, et l'oublier casse en silence. Les types valides
 * sont déjà gouvernés par le CODE (les constantes des contrôleurs) ; les
 * dupliquer dans le schéma ne fait que créer une dérive qui explose en prod.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE otp_tokens DROP CONSTRAINT IF EXISTS otp_tokens_type_check');
            DB::statement('ALTER TABLE otp_tokens ALTER COLUMN type TYPE varchar(50)');
        } else {
            Schema::table('otp_tokens', function (Blueprint $table) {
                $table->string('type', 50)->change();
            });
        }
    }

    public function down(): void
    {
        // On ne remet PAS la contrainte : elle n'autoriserait plus « client_login »
        // et re-casserait l'espace client. Le retour en arrière rétablit seulement
        // le type de colonne d'origine, sans l'énumération qui posait problème.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE otp_tokens ALTER COLUMN type TYPE varchar(50)');
        } else {
            Schema::table('otp_tokens', function (Blueprint $table) {
                $table->string('type', 50)->change();
            });
        }
    }
};
