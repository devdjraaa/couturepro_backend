<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            // Publication sur la vitrine publique. default(true) : les créations
            // existantes restent visibles (comportement actuel préservé).
            $table->boolean('publie_vitrine')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->dropColumn('publie_vitrine');
        });
    }
};
