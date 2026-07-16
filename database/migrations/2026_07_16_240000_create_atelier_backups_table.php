<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PL-10 : marqueur de sauvegarde cloud par atelier (snapshot de données cadencé).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atelier_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('atelier_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->unsignedBigInteger('taille')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['atelier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atelier_backups');
    }
};
