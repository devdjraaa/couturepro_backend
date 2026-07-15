<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P161-163 : patron (contenu numérique payant) attaché à une création publiée.
// Le créateur (offre premium) téléverse le fichier et fixe un prix ; les visiteurs
// l'achètent via FedaPay et le téléchargent ensuite avec leur code de transaction.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patrons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('vetement_id')->constrained('vetements')->cascadeOnDelete(); // la création vendue
            $table->string('titre');
            $table->text('description')->nullable();
            $table->unsignedInteger('prix');            // XOF
            $table->string('fichier_path');             // storage privé (jamais public)
            $table->string('fichier_nom')->nullable();  // nom d'origine pour le téléchargement
            $table->unsignedBigInteger('fichier_taille')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['atelier_id', 'actif']);
            $table->index('vetement_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patrons');
    }
};
