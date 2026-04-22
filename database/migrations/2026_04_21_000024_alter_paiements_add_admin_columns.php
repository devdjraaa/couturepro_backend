<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('paiements', function (Blueprint $table) {
            $table->foreignUuid('validated_by')->nullable()->after('expires_at')
                  ->constrained('admins')->nullOnDelete();
        });

        // Ajout FK sur transactions_abonnement.created_by maintenant que admins existe
        Schema::table('transactions_abonnement', function (Blueprint $table) {
            $table->foreign('created_by')->references('id')->on('admins')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('transactions_abonnement', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
        Schema::table('paiements', function (Blueprint $table) {
            $table->dropForeign(['validated_by']);
            $table->dropColumn('validated_by');
        });
    }
};
