<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE admins DROP CONSTRAINT IF EXISTS admins_role_check');
            DB::statement("ALTER TABLE admins ALTER COLUMN role SET DEFAULT 'admin'");
            DB::statement("ALTER TABLE admins ADD CONSTRAINT admins_role_check CHECK (role IN ('super_admin','admin','support'))");
        } else {
            DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('super_admin', 'admin', 'support') NOT NULL DEFAULT 'admin'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE admins DROP CONSTRAINT IF EXISTS admins_role_check');
            DB::statement("ALTER TABLE admins ADD CONSTRAINT admins_role_check CHECK (role IN ('super_admin','admin'))");
        } else {
            DB::statement("ALTER TABLE admins MODIFY COLUMN role ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin'");
        }
    }
};
