<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->string('specialite')->nullable();   // ex. « Haute couture », « Tailleur »
            $table->text('bio')->nullable();            // présentation publique du créateur
            $table->boolean('verifie')->default(false); // badge vérifié (réservé modération)
        });
    }

    public function down(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->dropColumn(['specialite', 'bio', 'verifie']);
        });
    }
};
