<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->string('verification_doc_path')->nullable();
            $table->string('verification_lien')->nullable();
            $table->timestamp('verification_demandee_a')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ateliers', function (Blueprint $table) {
            $table->dropColumn(['verification_doc_path', 'verification_lien', 'verification_demandee_a']);
        });
    }
};
