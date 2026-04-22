<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipe_membres', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->foreignUuid('created_by')->constrained('proprietaires');
            $table->string('code_acces', 60)->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100)->nullable();
            $table->enum('role', ['assistant', 'membre']);
            $table->string('password');
            $table->string('device_id', 255)->nullable();
            $table->timestamp('device_locked_at')->nullable();
            $table->timestamp('derniere_sync_at')->nullable();
            $table->string('code_reprise', 10)->nullable();
            $table->timestamp('code_reprise_expire_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('revoque_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('atelier_id');
            $table->index('code_acces');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipe_membres');
    }
};
