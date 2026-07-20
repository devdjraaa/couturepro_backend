<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pt 10 (lot 2, 20/07) — Type du document justificatif envoyé pour la
 * vérification Designer. La modération recevait un fichier sans savoir ce
 * qu'elle regardait : carte d'identité, registre de commerce, justificatif de
 * domicile… La liste des types vit en configuration (`VitrineSetting`), pas en
 * dur : la direction peut l'étendre sans redéploiement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->string('verification_doc_type', 40)->nullable()->after('verification_doc_path');
        });
    }

    public function down(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->dropColumn('verification_doc_type');
        });
    }
};
