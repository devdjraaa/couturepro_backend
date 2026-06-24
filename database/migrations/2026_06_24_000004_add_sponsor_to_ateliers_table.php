<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            // Mise en avant sponsorisée : sponsorisé tant que cette date est dans le futur.
            $table->timestamp('sponsor_jusqu_a')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->dropColumn('sponsor_jusqu_a');
        });
    }
};
