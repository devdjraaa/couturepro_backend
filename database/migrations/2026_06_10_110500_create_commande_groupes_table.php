<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commande_groupes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('atelier_id')->constrained('ateliers')->cascadeOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->cascadeOnDelete();
            $table->uuid('created_by');
            $table->string('created_by_role')->default('proprietaire');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->foreignUuid('commande_groupe_id')->nullable()->after('client_id')
                ->constrained('commande_groupes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('commande_groupe_id');
        });

        Schema::dropIfExists('commande_groupes');
    }
};
