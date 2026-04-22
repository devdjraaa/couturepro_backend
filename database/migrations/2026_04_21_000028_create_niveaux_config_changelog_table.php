<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('niveaux_config_changelog', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('niveau_cle', 50); // Pas de FK — survit à la suppression du plan
            $table->foreignUuid('admin_id')->constrained('admins');
            $table->string('champ_modifie', 100);
            $table->text('ancienne_valeur')->nullable();
            $table->text('nouvelle_valeur')->nullable();
            $table->timestamp('created_at');
            $table->index(['niveau_cle', 'created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('niveaux_config_changelog'); }
};
