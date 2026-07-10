<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications_systeme', function (Blueprint $table) {
            // Cible de redirection au tap (deep-link), ex. "/commandes/{id}", "/ma-vitrine".
            $table->string('lien')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('notifications_systeme', function (Blueprint $table) {
            $table->dropColumn('lien');
        });
    }
};
