<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parametres_atelier', function (Blueprint $table) {
            $table->enum('format_facture', ['standard', 'personnalise'])->default('standard');
            $table->string('facture_logo_path')->nullable();
            $table->string('facture_ifu', 100)->nullable();
            $table->string('facture_rccm', 100)->nullable();
            $table->text('facture_pied_page')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('parametres_atelier', function (Blueprint $table) {
            $table->dropColumn(['format_facture', 'facture_logo_path', 'facture_ifu', 'facture_rccm', 'facture_pied_page']);
        });
    }
};
