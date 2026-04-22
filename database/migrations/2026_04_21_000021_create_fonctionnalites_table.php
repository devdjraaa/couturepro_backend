<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('fonctionnalites', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('cle', 50)->unique();
            $table->string('label', 100);
            $table->string('description', 255)->nullable();
            $table->enum('type', ['booleen', 'numerique', 'points']);
            $table->string('unite', 30)->nullable();
            $table->enum('categorie', ['equipe', 'clients_commandes', 'communication', 'stockage', 'module', 'fidelite']);
            $table->string('valeur_defaut', 50)->nullable();
            $table->boolean('is_actif')->default(true);
            $table->tinyInteger('ordre_affichage')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('fonctionnalites'); }
};
