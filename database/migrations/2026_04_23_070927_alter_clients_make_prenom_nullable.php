<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('prenom', 100)->nullable()->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_type_profil_check');
            DB::statement("ALTER TABLE clients ALTER COLUMN type_profil SET DEFAULT 'mixte'");
            DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_type_profil_check CHECK (type_profil IN ('homme','femme','enfant','mixte'))");
        } else {
            Schema::table('clients', function (Blueprint $table) {
                $table->enum('type_profil', ['homme', 'femme', 'enfant', 'mixte'])->default('mixte')->change();
            });
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('prenom', 100)->nullable(false)->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE clients DROP CONSTRAINT IF EXISTS clients_type_profil_check');
            DB::statement("ALTER TABLE clients ALTER COLUMN type_profil DROP DEFAULT");
            DB::statement("ALTER TABLE clients ADD CONSTRAINT clients_type_profil_check CHECK (type_profil IN ('homme','femme','enfant','mixte'))");
        } else {
            Schema::table('clients', function (Blueprint $table) {
                $table->enum('type_profil', ['homme', 'femme', 'enfant', 'mixte'])->change();
            });
        }
    }
};
