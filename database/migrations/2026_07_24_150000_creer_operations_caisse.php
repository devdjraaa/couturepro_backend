<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal de caisse — les mouvements réels d'espèces.
 *
 * La « caisse » ne faisait que dériver les paiements de commandes (rapport
 * passif) : aucune façon de tenir sa caisse au quotidien — apport du matin,
 * achat de tissu, retrait, vente directe. C’est ce que la direction pointait
 * par « pas fonctionnelle du tout ». Ce journal apporte les entrées/sorties et
 * le solde d’espèces réel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operations_caisse', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            // entree = de l'argent rentre (apport, vente, encaissement espèces) ;
            // sortie = de l'argent sort (dépense, achat, retrait, salaire).
            $table->string('type', 10); // entree | sortie
            $table->decimal('montant', 12, 2);
            $table->string('motif', 200);
            // Comment l'argent a bougé — utile pour rapprocher avec le mobile money.
            $table->string('mode', 20)->default('especes'); // especes | mobile_money | virement | autre
            $table->uuid('created_by')->nullable();
            $table->string('created_by_role', 20)->nullable(); // proprietaire | membre
            $table->timestamps();

            $table->index(['atelier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operations_caisse');
    }
};
