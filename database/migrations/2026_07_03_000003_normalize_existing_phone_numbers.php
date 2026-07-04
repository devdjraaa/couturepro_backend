<?php

use App\Models\Proprietaire;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Met les numéros existants sous la forme canonique (« + » + chiffres, sans espaces),
    // pour que la connexion des comptes déjà créés continue de fonctionner après la
    // normalisation appliquée à l'inscription / au login.
    public function up(): void
    {
        // Propriétaires (inclut les soft-deleted : on normalise tout via query builder).
        foreach (DB::table('proprietaires')->select('id', 'telephone')->cursor() as $row) {
            $norm = Proprietaire::normalizePhone($row->telephone);
            if ($norm !== null && $norm !== $row->telephone) {
                DB::table('proprietaires')->where('id', $row->id)->update(['telephone' => $norm]);
            }
        }

        // Jetons OTP en attente (mêmes numéros, pour que la vérification matche).
        if (DB::getSchemaBuilder()->hasTable('otp_tokens')) {
            foreach (DB::table('otp_tokens')->select('id', 'telephone')->cursor() as $row) {
                $norm = Proprietaire::normalizePhone($row->telephone);
                if ($norm !== null && $norm !== $row->telephone) {
                    DB::table('otp_tokens')->where('id', $row->id)->update(['telephone' => $norm]);
                }
            }
        }
    }

    public function down(): void
    {
        // Irréversible : on ne peut pas restaurer le formatage d'origine (espaces perdus).
    }
};
