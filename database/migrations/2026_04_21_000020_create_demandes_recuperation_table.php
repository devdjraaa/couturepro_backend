<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('demandes_recuperation', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email', 255);
            $table->string('telephone_nouveau', 25)->nullable();
            $table->enum('statut', ['en_attente_email','email_confirme','en_attente_otp','complete','expire','bloque'])->default('en_attente_email');
            $table->string('token_opposition', 100)->unique();
            $table->timestamp('opposition_expire_at');
            $table->boolean('otp_envoye')->default(false);
            $table->timestamp('validated_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
            $table->index('email');
        });
    }
    public function down(): void { Schema::dropIfExists('demandes_recuperation'); }
};
