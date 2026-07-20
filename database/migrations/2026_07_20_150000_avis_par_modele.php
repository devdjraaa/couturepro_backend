<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Avis v2 — décisions direction du 20/07 (document « décisions arrêtées »).
 *
 * L'avis est désormais rattaché au MODÈLE (ni au créateur, ni à la collection —
 * la piste « collection » du 19/07 est abandonnée, la colonne reste pour les
 * lignes historiques). Compte client obligatoire, un seul avis par personne et
 * par modèle, signalements motivés avec seuil, photos validées AVANT publication.
 *
 * Les 5 avis existants (anonymes, rattachés au créateur) sont conservés tels
 * quels : `vetement_id` nul = avis « historique », toujours affiché côté
 * créateur. Aucune donnée perdue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            // Décision 1 : l'avis vise un modèle précis.
            $table->uuid('vetement_id')->nullable()->after('atelier_id');
            $table->foreign('vetement_id')->references('id')->on('vetements')->nullOnDelete();

            // Décision 9 : champ prévu dès maintenant, logique développée plus tard.
            $table->boolean('achat_verifie')->default(false)->after('commande_id');

            // Décision 7 : un signalement grave court-circuite le seuil.
            $table->boolean('revue_prioritaire')->default(false)->after('signalements_count');

            // Décision 11 : les photos jointes attendent la validation admin
            // (null = avis sans photo ; en_attente / validees / refusees).
            $table->string('photos_statut', 20)->nullable()->after('photos');
        });

        // Décision 2 : un seul avis par compte et par modèle. Les lignes
        // historiques (client nul, modèle nul) ne sont pas concernées : les
        // valeurs nulles ne se heurtent pas dans un index unique.
        Schema::table('avis', function (Blueprint $table) {
            $table->unique(['gxt_client_id', 'vetement_id'], 'avis_un_par_client_et_modele');
        });

        // Les photos déjà en ligne ont été publiées sous l'ancien régime : les
        // repasser « en attente » les ferait disparaître des profils du jour au
        // lendemain. Elles sont réputées validées.
        DB::table('avis')->whereNotNull('photos')->update(['photos_statut' => 'validees']);

        // Décision 7 : signalements individuels et motivés. L'ancien compteur
        // (`signalements_count`) reste alimenté, mais la vérité est ici :
        // une empreinte (clé visiteur ou compte) ne compte qu'une fois par avis.
        Schema::create('avis_signalements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('avis_id');
            $table->foreign('avis_id')->references('id')->on('avis')->cascadeOnDelete();
            $table->string('empreinte', 64);          // visitor_key ou id du compte client
            $table->string('motif', 30)->nullable();  // contenu_illegal | insulte | discrimination | autre
            $table->timestamp('created_at')->nullable();
            $table->unique(['avis_id', 'empreinte'], 'signalement_unique_par_personne');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avis_signalements');
        Schema::table('avis', function (Blueprint $table) {
            $table->dropUnique('avis_un_par_client_et_modele');
            $table->dropForeign(['vetement_id']);
            $table->dropColumn(['vetement_id', 'achat_verifie', 'revue_prioritaire', 'photos_statut']);
        });
    }
};
