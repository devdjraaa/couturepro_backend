<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('otp_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('telephone', 25);
            $table->string('code'); // bcrypt hashed
            $table->enum('type', ['verification_inscription', 'recuperation_compte']);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->tinyInteger('tentatives_echec')->default(0);
            $table->timestamp('created_at');
            $table->index(['telephone', 'type']);
        });
    }
    public function down(): void { Schema::dropIfExists('otp_tokens'); }
};
