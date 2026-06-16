<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE otp_tokens DROP CONSTRAINT IF EXISTS otp_tokens_type_check');
            DB::statement("ALTER TABLE otp_tokens ADD CONSTRAINT otp_tokens_type_check CHECK (type IN ('verification_inscription','recuperation_compte','recuperation_nouveau_telephone'))");
        } else {
            Schema::table('otp_tokens', function (Blueprint $table) {
                $table->enum('type', [
                    'verification_inscription',
                    'recuperation_compte',
                    'recuperation_nouveau_telephone',
                ])->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE otp_tokens DROP CONSTRAINT IF EXISTS otp_tokens_type_check');
            DB::statement("ALTER TABLE otp_tokens ADD CONSTRAINT otp_tokens_type_check CHECK (type IN ('verification_inscription','recuperation_compte'))");
        } else {
            Schema::table('otp_tokens', function (Blueprint $table) {
                $table->enum('type', [
                    'verification_inscription',
                    'recuperation_compte',
                ])->change();
            });
        }
    }
};
