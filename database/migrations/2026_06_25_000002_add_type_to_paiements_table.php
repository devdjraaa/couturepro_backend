<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            // 'abonnement' (défaut) ou 'sponsorisation' (mise en avant vitrine).
            $table->string('type', 30)->default('abonnement')->index();
            $table->json('meta')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropColumn(['type', 'meta']);
        });
    }
};
