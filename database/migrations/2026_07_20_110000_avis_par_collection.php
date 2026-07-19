<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * S08C-29e — Avis rattachés à une COLLECTION (décision direction du 20/07).
 *
 * Les avis n'existaient qu'au niveau du créateur (`atelier_id`) : la notion d'avis
 * par collection, supposée par la demande, n'existait nulle part.
 *
 * Le lien est FACULTATIF : un avis peut viser une collection précise ou le créateur
 * dans son ensemble. Les avis existants restent donc valides, rattachés au créateur.
 * `nullOnDelete` : supprimer une collection ne détruit pas les avis, ils remontent
 * simplement au niveau du créateur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->foreignUuid('collection_id')->nullable()->after('atelier_id')
                ->constrained('collections')->nullOnDelete();

            $table->index(['collection_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->dropIndex(['collection_id', 'statut']);
            $table->dropConstrainedForeignId('collection_id');
        });
    }
};
