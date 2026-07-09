<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Avant l'introduction du type de compte, tout atelier non-démo était éligible
     * à la vitrine publique. Pour ne rien retirer de la vitrine existante, on backfille
     * les comptes sans type en « designer ». Les nouveaux comptes choisissent leur type
     * à l'inscription (défaut artisan). Un artisan existant pourra basculer dans Paramètres.
     */
    public function up(): void
    {
        DB::table('ateliers')->whereNull('type')->update(['type' => 'designer']);
    }

    public function down(): void
    {
        // Irréversible : impossible de distinguer les valeurs backfillées des choix explicites.
    }
};
