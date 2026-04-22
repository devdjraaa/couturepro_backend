<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ateliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('proprietaire_id')->constrained('proprietaires')->cascadeOnDelete();
            $table->string('nom', 150);
            $table->boolean('is_maitre')->default(false);
            $table->enum('statut', ['actif', 'expire', 'essai', 'gele'])->default('essai');
            $table->timestamp('essai_expire_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('proprietaire_id');
            $table->index('statut');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ateliers');
    }
};
