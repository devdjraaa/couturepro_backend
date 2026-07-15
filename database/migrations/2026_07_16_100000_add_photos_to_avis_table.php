<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P137 : photos jointes à un avis (ex. le client portant l'article) — renforce la confiance.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->json('photos')->nullable()->after('texte'); // tableau de chemins (disque public)
        });
    }

    public function down(): void
    {
        Schema::table('avis', function (Blueprint $table) {
            $table->dropColumn('photos');
        });
    }
};
