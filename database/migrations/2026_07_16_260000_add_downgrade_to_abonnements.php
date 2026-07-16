<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P53-55 (option A) : downgrade différé — le plan inférieur choisi s'applique
// automatiquement à l'échéance du plan courant (annulable avant).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->string('downgrade_vers_cle')->nullable()->after('config_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('abonnements', function (Blueprint $table) {
            $table->dropColumn('downgrade_vers_cle');
        });
    }
};
