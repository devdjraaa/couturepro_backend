<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions_equipe', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->enum('role', ['assistant', 'membre']);
            $table->string('ressource', 50);
            $table->string('action', 30);
            $table->boolean('autorise')->default(true);
            $table->timestamps();

            $table->unique(['atelier_id', 'role', 'ressource', 'action']);
            $table->index(['atelier_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions_equipe');
    }
};
