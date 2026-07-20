<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lot 2 direction (20/07) — consentements séparés et champs de profil.
 *
 * 1. CONSENTEMENTS (espace client) : la politique de confidentialité et la
 *    newsletter sont deux consentements DISTINCTS. Jamais fusionnés, jamais
 *    précochés. La version de politique acceptée est tracée : si le texte change,
 *    on saura qui a accepté quoi (exigence APDP/RGPD).
 *
 * 2. PROFIL PRO : `date_naissance` (le module d'événements en a besoin pour
 *    l'anniversaire — il n'existait que côté client), `pseudo` et `identifiant
 *    public` demandés au lot précédent.
 *
 * Aucune valeur par défaut « accepté » : un consentement rétroactif n'en est pas
 * un. Les comptes existants restent à null et seront invités à se prononcer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gxt_clients', function (Blueprint $table) {
            $table->boolean('privacy_policy_accepted')->default(false);
            $table->timestamp('privacy_policy_accepted_at')->nullable();
            $table->string('privacy_policy_version', 20)->nullable();

            $table->boolean('newsletter_opt_in')->default(false);
            $table->timestamp('newsletter_opt_in_at')->nullable();
        });

        Schema::table('proprietaires', function (Blueprint $table) {
            // Jour et mois suffisent (précision demandée par la direction) mais on
            // stocke une date complète : c'est le type juste, et l'année reste
            // facultative côté saisie.
            $table->date('date_naissance')->nullable();
            $table->string('pseudo', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gxt_clients', function (Blueprint $table) {
            $table->dropColumn([
                'privacy_policy_accepted', 'privacy_policy_accepted_at', 'privacy_policy_version',
                'newsletter_opt_in', 'newsletter_opt_in_at',
            ]);
        });

        Schema::table('proprietaires', function (Blueprint $table) {
            $table->dropColumn(['date_naissance', 'pseudo']);
        });
    }
};
