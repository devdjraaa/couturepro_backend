<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// PL-2 : l'export groupé (mesures, puis collections/patrons) est réservé aux plans
// payants — la maquette officielle du plan Gratuit le dit explicitement :
// « export mesure client par client (groupé = payant) ».
return new class extends Migration
{
    private array $payants = [
        'standard_mensuel', 'standard_annuel',
        'premium_mensuel', 'premium_annuel',
        'magnat_mensuel', 'magnat_annuel',
        'atelier_mensuel', 'atelier_annuel',
        'master_mensuel', 'master_annuel',
    ];

    public function up(): void
    {
        foreach ($this->payants as $cle) {
            $this->merge($cle, ['export_groupe' => true]);
        }
        $this->merge('free', ['export_groupe' => false]);
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
        foreach ([...$this->payants, 'free'] as $cle) {
            $row = DB::table('niveaux_config')->where('cle', $cle)->first();
            if (! $row) {
                continue;
            }
            $config = is_string($row->config) ? (json_decode($row->config, true) ?: []) : (array) $row->config;
            unset($config['export_groupe']);
            DB::table('niveaux_config')->where('cle', $cle)->update([
                'config'     => json_encode($config, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }
};
