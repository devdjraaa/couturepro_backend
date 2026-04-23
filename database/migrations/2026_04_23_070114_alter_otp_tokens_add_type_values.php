<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_tokens', function (Blueprint $table) {
            $table->enum('type', [
                'verification_inscription',
                'recuperation_compte',
                'recuperation_nouveau_telephone',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('otp_tokens', function (Blueprint $table) {
            $table->enum('type', [
                'verification_inscription',
                'recuperation_compte',
            ])->change();
        });
    }
};
