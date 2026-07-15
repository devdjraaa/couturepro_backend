<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P177 : liens réseaux sociaux optionnels du profil créateur. instagram/facebook/site_web
// existaient déjà ; on complète avec linkedin, youtube et tiktok.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->string('linkedin')->nullable()->after('site_web');
            $table->string('youtube')->nullable()->after('linkedin');
            $table->string('tiktok')->nullable()->after('youtube');
        });
    }

    public function down(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->dropColumn(['linkedin', 'youtube', 'tiktok']);
        });
    }
};
