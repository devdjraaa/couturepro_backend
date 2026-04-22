<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('niveaux_config', function (Blueprint $table) {
            $table->foreignUuid('updated_by')->nullable()->after('description_courte')
                  ->constrained('admins')->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('niveaux_config', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });
    }
};
