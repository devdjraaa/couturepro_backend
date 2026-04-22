<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('liste_noire', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('type', ['telephone', 'email', 'ip']);
            $table->string('valeur', 255);
            $table->text('raison')->nullable();
            $table->foreignUuid('admin_id')->constrained('admins');
            $table->timestamps();
            $table->unique(['type', 'valeur']);
            $table->index(['type', 'valeur']);
        });
    }
    public function down(): void { Schema::dropIfExists('liste_noire'); }
};
