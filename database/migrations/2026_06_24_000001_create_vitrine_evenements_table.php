<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vitrine_evenements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('atelier_id')->index();
            $table->string('type'); // visite | contact
            $table->timestamps();
            $table->index(['atelier_id', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vitrine_evenements');
    }
};
