<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('admin_audit_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignUuid('admin_id')->constrained('admins');
            $table->string('action', 100);
            $table->string('entite_type', 50)->nullable();
            $table->string('entite_id', 36)->nullable();
            $table->json('details')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at');
            $table->index('admin_id');
            $table->index('action');
            $table->index(['entite_type', 'entite_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('admin_audit_log'); }
};
