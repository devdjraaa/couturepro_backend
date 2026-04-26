<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Supprimer d'abord toutes les FK qui bloquent les drops d'index
        DB::statement('ALTER TABLE mesures DROP FOREIGN KEY mesures_client_id_foreign');
        DB::statement('ALTER TABLE mesures DROP FOREIGN KEY mesures_vetement_id_foreign');
        // Supprimer les index (plus bloqués par les FK)
        DB::statement('ALTER TABLE mesures DROP INDEX mesures_client_id_vetement_id_unique');
        DB::statement('ALTER TABLE mesures DROP INDEX mesures_vetement_id_foreign');
        DB::statement('ALTER TABLE mesures DROP COLUMN vetement_id');
        // Restore: standalone index for client_id FK + re-add FK + new unique constraint
        DB::statement('ALTER TABLE mesures ADD INDEX mesures_client_id_index (client_id)');
        DB::statement('ALTER TABLE mesures ADD CONSTRAINT mesures_client_id_foreign FOREIGN KEY (client_id) REFERENCES clients (id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE mesures ADD UNIQUE KEY mesures_atelier_id_client_id_unique (atelier_id, client_id)');
    }

    public function down(): void
    {
        Schema::table('mesures', function (Blueprint $table) {
            $table->dropUnique(['atelier_id', 'client_id']);
            $table->foreignUuid('vetement_id')->nullable()->constrained('vetements');
            $table->unique(['client_id', 'vetement_id']);
        });
    }
};
