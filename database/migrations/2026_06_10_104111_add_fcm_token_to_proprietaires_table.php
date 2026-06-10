<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->string('fcm_token', 512)->nullable()->after('email');
            $table->string('fcm_platform', 20)->nullable()->after('fcm_token'); // ios | android | web
        });
    }

    public function down(): void
    {
        Schema::table('proprietaires', function (Blueprint $table) {
            $table->dropColumn(['fcm_token', 'fcm_platform']);
        });
    }
};
