<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Le nom de la structure (atelier / marque) saisi à l'inscription est capté ici, puis utilisé
    // pour nommer l'atelier lors de la vérification OTP — au lieu d'un nom généré en dur.
    public function up(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->string('nom_atelier', 150)->nullable()->after('prenom');
        });
    }

    public function down(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->dropColumn('nom_atelier');
        });
    }
};
