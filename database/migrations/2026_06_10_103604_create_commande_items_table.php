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
        Schema::create('commande_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('commande_id')->constrained('commandes')->cascadeOnDelete();
            $table->foreignUuid('vetement_id')->nullable()->constrained('vetements')->nullOnDelete();
            $table->string('vetement_nom', 150)->nullable();
            $table->unsignedSmallInteger('quantite')->default(1);
            $table->decimal('prix_unitaire', 10, 2)->default(0);
            $table->string('description', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commande_items');
    }
};
