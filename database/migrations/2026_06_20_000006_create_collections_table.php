<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('atelier_id')->index();
            $table->string('nom');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('vetements', function (Blueprint $table) {
            $table->uuid('collection_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('vetements', function (Blueprint $table) {
            $table->dropColumn('collection_id');
        });
        Schema::dropIfExists('collections');
    }
};
