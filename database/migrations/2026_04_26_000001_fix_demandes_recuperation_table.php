<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('demandes_recuperation', function (Blueprint $table) {
            $table->string('token_opposition', 100)->nullable()->change();
            $table->timestamp('opposition_expire_at')->nullable()->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE demandes_recuperation DROP CONSTRAINT IF EXISTS demandes_recuperation_statut_check');
            DB::statement("ALTER TABLE demandes_recuperation ALTER COLUMN statut SET DEFAULT 'etape_1'");
            DB::statement("ALTER TABLE demandes_recuperation ADD CONSTRAINT demandes_recuperation_statut_check CHECK (statut IN ('etape_1','etape_2','etape_3','etape_4','complete','expire','bloque','en_attente_email','email_confirme','en_attente_otp'))");
        } else {
            DB::statement("ALTER TABLE demandes_recuperation
                MODIFY COLUMN statut ENUM(
                    'etape_1','etape_2','etape_3','etape_4',
                    'complete','expire','bloque',
                    'en_attente_email','email_confirme','en_attente_otp'
                ) NOT NULL DEFAULT 'etape_1'");
        }
    }

    public function down(): void
    {
        Schema::table('demandes_recuperation', function (Blueprint $table) {
            $table->string('token_opposition', 100)->nullable(false)->change();
            $table->timestamp('opposition_expire_at')->nullable(false)->change();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE demandes_recuperation DROP CONSTRAINT IF EXISTS demandes_recuperation_statut_check');
            DB::statement("ALTER TABLE demandes_recuperation ALTER COLUMN statut SET DEFAULT 'en_attente_email'");
            DB::statement("ALTER TABLE demandes_recuperation ADD CONSTRAINT demandes_recuperation_statut_check CHECK (statut IN ('en_attente_email','email_confirme','en_attente_otp','complete','expire','bloque'))");
        } else {
            DB::statement("ALTER TABLE demandes_recuperation
                MODIFY COLUMN statut ENUM(
                    'en_attente_email','email_confirme','en_attente_otp',
                    'complete','expire','bloque'
                ) NOT NULL DEFAULT 'en_attente_email'");
        }
    }
};
