<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('prenom', 100)->nullable()->change();
            $table->enum('type_profil', ['homme', 'femme', 'enfant', 'mixte'])->default('mixte')->change();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('prenom', 100)->nullable(false)->change();
            $table->enum('type_profil', ['homme', 'femme', 'enfant', 'mixte'])->change();
        });
    }
};
