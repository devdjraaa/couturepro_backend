<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P134 : bannière (image de couverture) du profil créateur — photo, GIF ou courte vidéo.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->string('banniere_path')->nullable()->after('logo_path');
            $table->string('banniere_type', 10)->nullable()->after('banniere_path'); // image | video
        });
    }

    public function down(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->dropColumn(['banniere_path', 'banniere_type']);
        });
    }
};
