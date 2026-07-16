<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PL-7 : vidéos de présentation (Studio) — liens (YouTube/hébergé), jusqu'à 50.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atelier_videos', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('atelier_id')->constrained()->cascadeOnDelete();
            $table->string('titre')->nullable();
            $table->string('url');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index('atelier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atelier_videos');
    }
};
