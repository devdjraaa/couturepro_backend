<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('reference')->nullable()->unique(); // n° public de suivi (GEX-XXXXXX)
            $table->string('etape')->default('commande');      // commande|coupe|confection|essayage|livraison
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['reference', 'etape']);
        });
    }
};
