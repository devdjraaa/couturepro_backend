<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P152 : bibliothèque photos catégorisée (référence / occasion / type de tenue…).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos_vip', function (Blueprint $table) {
            $table->string('categorie')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('photos_vip', function (Blueprint $table) {
            $table->dropColumn('categorie');
        });
    }
};
