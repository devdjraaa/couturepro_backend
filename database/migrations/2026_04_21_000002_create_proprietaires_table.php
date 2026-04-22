<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proprietaires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('telephone', 25)->unique();
            $table->string('email')->unique();
            $table->string('nom', 100);
            $table->string('prenom', 100);
            $table->string('question_secrete');
            $table->string('reponse_secrete'); // bcrypt hashed
            $table->string('password');        // bcrypt hashed
            $table->rememberToken();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('telephone_verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proprietaires');
    }
};
