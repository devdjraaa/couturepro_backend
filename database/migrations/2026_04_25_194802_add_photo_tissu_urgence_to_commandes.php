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
        Schema::table('commandes', function (Blueprint $table) {
            $table->string('photo_tissu_path')->nullable()->after('note_interne');
            $table->boolean('urgence')->default(false)->after('photo_tissu_path');
            $table->text('description')->nullable()->after('urgence');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['photo_tissu_path', 'urgence', 'description']);
        });
    }
};
