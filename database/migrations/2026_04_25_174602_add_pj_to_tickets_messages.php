<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets_messages', function (Blueprint $table) {
            $table->string('pj_path', 500)->nullable()->after('contenu');
        });
    }

    public function down(): void
    {
        Schema::table('tickets_messages', function (Blueprint $table) {
            $table->dropColumn('pj_path');
        });
    }
};
