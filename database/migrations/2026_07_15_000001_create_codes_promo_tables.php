<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// P153-158 : système de codes promo / ambassadeurs.
//  - codes_promo : le code lui-même (événement à expiration OU ambassadeur permanent),
//    chaque code ajoute `jours_bonus` au temps restant (essai OU abonnement) — P155.
//  - code_promo_utilisations : 1 utilisation par téléphone et par code (P154/P158).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codes_promo', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 40)->unique();               // ex. GEXTIMO15JUILLET, GEXT-AMB-001
            $table->enum('type', ['evenement', 'ambassadeur'])->default('evenement');
            $table->unsignedSmallInteger('jours_bonus')->default(17);
            $table->timestamp('expire_at')->nullable();          // null = pas d'expiration (ambassadeur)
            $table->unsignedInteger('max_utilisations')->nullable(); // null = illimité
            $table->boolean('is_actif')->default(true);
            $table->string('note')->nullable();                  // libellé interne (ex. « Ambassadeur #1 — Marie »)
            $table->uuid('created_by')->nullable();              // admin créateur
            $table->timestamps();

            $table->index(['is_actif', 'expire_at']);
        });

        Schema::create('code_promo_utilisations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('code_promo_id');
            $table->uuid('proprietaire_id');
            $table->string('telephone', 30);                     // normalisé — clé d'unicité métier
            $table->uuid('atelier_id')->nullable();
            $table->timestamps();

            $table->foreign('code_promo_id')->references('id')->on('codes_promo')->cascadeOnDelete();
            $table->unique(['code_promo_id', 'telephone']);      // 1× par téléphone et par code
            $table->index('proprietaire_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('code_promo_utilisations');
        Schema::dropIfExists('codes_promo');
    }
};
