<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            // Opt-in : le créateur choisit d'exposer son contact (WhatsApp) sur sa
            // vitrine publique. default(false) → rien n'est exposé sans action.
            $table->boolean('contact_public')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->dropColumn('contact_public');
        });
    }
};
