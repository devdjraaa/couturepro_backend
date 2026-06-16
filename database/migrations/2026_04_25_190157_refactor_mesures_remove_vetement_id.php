<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesures', function (Blueprint $table) {
            $table->dropForeign('mesures_client_id_foreign');
            $table->dropForeign('mesures_vetement_id_foreign');
            $table->dropUnique('mesures_client_id_vetement_id_unique');
            // MySQL auto-crée un index séparé pour les FK ; PostgreSQL non — on ignore si absent
            if (DB::getDriverName() !== 'pgsql') {
                $table->dropIndex('mesures_vetement_id_foreign');
            }
            $table->dropColumn('vetement_id');
        });

        Schema::table('mesures', function (Blueprint $table) {
            $table->foreign('client_id')->references('id')->on('clients')->onDelete('cascade');
            $table->unique(['atelier_id', 'client_id']);
        });
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
