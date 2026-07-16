<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P77 : « cliente partagée entre ateliers » — un client marqué partagé apparaît
// dans tous les ateliers du propriétaire (comptes multi-ateliers).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('partage')->default(false)->index();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('partage');
        });
    }
};
