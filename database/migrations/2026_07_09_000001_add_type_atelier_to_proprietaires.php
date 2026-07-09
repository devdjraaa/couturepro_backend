<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Le type de compte (artisan | designer) choisi à l'inscription est capté ici,
    // puis reporté sur l'atelier lors de la vérification OTP (comme nom_atelier).
    public function up(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->string('type_atelier', 20)->nullable()->after('nom_atelier');
        });
    }

    public function down(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->dropColumn('type_atelier');
        });
    }
};
