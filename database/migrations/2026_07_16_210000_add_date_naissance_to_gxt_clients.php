<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Brief 16/07 (points 3+6) : date de naissance optionnelle sur le client vitrine,
// pour les vœux d'anniversaire (gxt:anniversaires) et la personnalisation.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gxt_clients', function (Blueprint $table) {
            $table->date('date_naissance')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('gxt_clients', fn (Blueprint $t) => $t->dropColumn('date_naissance'));
    }
};
