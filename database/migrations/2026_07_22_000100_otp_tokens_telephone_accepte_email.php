<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `otp_tokens.telephone` : 25 → 150 caractères.
 *
 * POURQUOI — la colonne a été dimensionnée pour un numéro de téléphone (25),
 * mais l'espace client vitrine réutilise la même table en y mettant l'E-MAIL
 * comme clé de l'OTP. Un e-mail dépasse vite 25 caractères
 * (« prenom.nom@fournisseur.com ») → « value too long for type varchar(25) » →
 * 500 à chaque demande de code. Le premier correctif (contrainte de type) avait
 * démasqué celui-ci, caché derrière.
 *
 * 150 = la limite déjà validée côté requête pour l'e-mail (`max:150`). Un numéro
 * y tient sans problème ; on n'a pas besoin de deux colonnes pour une clé qui
 * est tantôt un téléphone, tantôt un e-mail.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otp_tokens', function (Blueprint $table) {
            $table->string('telephone', 150)->change();
        });
    }

    public function down(): void
    {
        // Pas de rétrécissement : des e-mails > 25 seraient tronqués et on
        // recasserait l'espace client. On laisse la colonne large.
    }
};
