<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            // Remplacer avatar_key (string) par avatar_index (int 0-6)
            $table->dropColumn('avatar_key');
            $table->unsignedTinyInteger('avatar_index')->nullable()->after('type_profil');

            // Statut VIP séparé du profil genre
            $table->boolean('is_vip')->default(false)->after('avatar_index');

            // Notes libres
            $table->text('notes')->nullable()->after('is_vip');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('avatar_key', 60)->nullable();
            $table->dropColumn(['avatar_index', 'is_vip', 'notes']);
        });
    }
};
