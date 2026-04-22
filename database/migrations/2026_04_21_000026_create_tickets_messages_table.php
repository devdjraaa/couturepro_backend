<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tickets_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ticket_id')->constrained('tickets_support')->cascadeOnDelete();
            $table->enum('expediteur_type', ['proprietaire', 'admin']);
            $table->uuid('expediteur_id');
            $table->text('contenu');
            $table->boolean('is_note_interne')->default(false);
            $table->timestamp('lu_par_client_at')->nullable();
            $table->timestamp('lu_par_admin_at')->nullable();
            $table->timestamp('created_at');
            $table->index('ticket_id');
            $table->index(['expediteur_type', 'expediteur_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('tickets_messages'); }
};
