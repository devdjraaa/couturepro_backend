<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PL-6 : annonce de collection (Studio) — message mis en avant + date de publication.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->text('annonce_message')->nullable();
            $table->timestamp('annonce_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropColumn(['annonce_message', 'annonce_at']);
        });
    }
};
