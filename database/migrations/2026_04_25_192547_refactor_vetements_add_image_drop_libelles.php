<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('nom');
            $table->dropColumn('libelles_mesures');
        });
    }

    public function down(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->dropColumn('image_path');
            $table->json('libelles_mesures')->nullable()->after('nom');
        });
    }
};
