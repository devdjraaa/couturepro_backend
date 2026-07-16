<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Logique métier définitive du plan Gratuit (direction, 16/07/2026) :
//  1. Vitrine : 5 ACTES de publication par période d'abonnement (reset à l'anniversaire),
//     et non plus un cap de créations publiées — d'où le journal `publications_vitrine`.
//  2. Facturation : plus de limite sur le NOMBRE de factures ; limite de 10 CLIENTS
//     DIFFÉRENTS facturés par période (factures illimitées pour un client déjà compté).
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('publications_vitrine')) {
            Schema::create('publications_vitrine', function (Blueprint $table) {
                $table->id();
                $table->uuid('atelier_id')->index();
                $table->uuid('vetement_id');
                $table->timestamp('created_at')->useCurrent();

                $table->index(['atelier_id', 'created_at']);
            });
        }

        // free : nouveaux quotas (publications/période + clients facturés/période),
        // plus de cap simultané ni de plafond de factures.
        $this->merge('free', [
            'publications_par_periode'     => 5,
            'max_creations_vitrine'        => null,
            'max_clients_factures_periode' => 10,
            'max_factures_par_mois'        => null,
        ]);

        // plans payants : pas de quota d'actes ni de clients facturés (cap simultané 25/50 conservé).
        foreach (['atelier_mensuel', 'atelier_annuel', 'master_mensuel', 'master_annuel'] as $cle) {
            $this->merge($cle, [
                'publications_par_periode'     => null,
                'max_clients_factures_periode' => null,
            ]);
        }
    }

    private function merge(string $cle, array $nouvellesCles): void
    {
        $row = DB::table('niveaux_config')->where('cle', $cle)->first();
        if (! $row) {
            return;
        }

        $config = is_string($row->config) ? (json_decode($row->config, true) ?: []) : (array) $row->config;

        DB::table('niveaux_config')->where('cle', $cle)->update([
            'config'     => json_encode(array_merge($config, $nouvellesCles), JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('publications_vitrine');

        // Restaure les anciens plafonds du plan gratuit (valeurs d'avant ce changement).
        $this->merge('free', [
            'publications_par_periode'     => null,
            'max_creations_vitrine'        => 5,
            'max_clients_factures_periode' => null,
            'max_factures_par_mois'        => 10,
        ]);
    }
};
