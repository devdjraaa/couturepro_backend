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
            DB::statement('ALTER TABLE notifications_systeme DROP CONSTRAINT IF EXISTS notifications_systeme_type_check');
            DB::statement("ALTER TABLE notifications_systeme ADD CONSTRAINT notifications_systeme_type_check CHECK (type IN ('promo','mise_a_jour','alerte_sync','alerte_abonnement','info','alerte_archive'))");
        } else {
            DB::statement("ALTER TABLE notifications_systeme MODIFY COLUMN type ENUM('promo','mise_a_jour','alerte_sync','alerte_abonnement','info','alerte_archive')");
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->string('archive_note', 500)->nullable()->after('archived_by');
        });

        Schema::table('commandes', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false)->after('statut');
            $table->timestamp('archived_at')->nullable()->after('is_archived');
            $table->uuid('archived_by')->nullable()->after('archived_at');
            $table->string('archive_note', 500)->nullable()->after('archived_by');
            $table->index(['atelier_id', 'is_archived']);
        });

        Schema::table('mesures', function (Blueprint $table) {
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->uuid('archived_by')->nullable();
            $table->string('archive_note', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('clients', fn (Blueprint $table) => $table->dropColumn('archive_note'));

        Schema::table('commandes', function (Blueprint $table) {
            $table->dropIndex('commandes_atelier_id_is_archived_index');
            $table->dropColumn(['is_archived', 'archived_at', 'archived_by', 'archive_note']);
        });

        Schema::table('mesures', fn (Blueprint $table) =>
            $table->dropColumn(['is_archived', 'archived_at', 'archived_by', 'archive_note'])
        );

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE notifications_systeme DROP CONSTRAINT IF EXISTS notifications_systeme_type_check');
            DB::statement("ALTER TABLE notifications_systeme ADD CONSTRAINT notifications_systeme_type_check CHECK (type IN ('promo','mise_a_jour','alerte_sync','alerte_abonnement','info'))");
        } else {
            DB::statement("ALTER TABLE notifications_systeme MODIFY COLUMN type ENUM('promo','mise_a_jour','alerte_sync','alerte_abonnement','info')");
        }
    }
};
