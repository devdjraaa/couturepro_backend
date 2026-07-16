<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Flags de gating des fonctionnalités de plan (PL-1..PL-10, maquette officielle).
// Atelier+ = atelier & master (mensuel/annuel) ; Studio = master (mensuel/annuel) uniquement.
return new class extends Migration
{
    // Plans designer officiels + legacy payants (standard≈atelier, premium/magnat≈studio)
    // pour ne priver aucun abonné payant existant de ces fonctionnalités.
    private array $atelier = [
        'atelier_mensuel', 'atelier_annuel', 'master_mensuel', 'master_annuel',
        'standard_mensuel', 'standard_annuel', 'premium_mensuel', 'premium_annuel', 'magnat_mensuel', 'magnat_annuel',
    ];
    private array $studio  = [
        'master_mensuel', 'master_annuel',
        'premium_mensuel', 'premium_annuel', 'magnat_mensuel', 'magnat_annuel',
    ];

    // feature => plans qui l'incluent (les autres reçoivent false).
    private array $features;

    public function __construct()
    {
        $this->features = [
            'lookbook_pdf'        => $this->atelier, // PL-1
            'rapport_mensuel'     => $this->atelier, // PL-3
            'liste_attente'       => $this->studio,  // PL-4
            'simulateur_revenus'  => $this->studio,  // PL-5
            'annonce_collection'  => $this->studio,  // PL-6
            'videos_presentation' => $this->studio,  // PL-7
            'badge_designer_pro'  => $this->atelier, // PL-8 (badge Atelier ; Studio a déjà le badge vérifié)
            'fidelite_avancee'    => $this->studio,  // PL-9
            'backup_cloud'        => $this->atelier, // PL-10 (cadence gérée par sauvegarde_auto existant)
        ];
    }

    public function up(): void
    {
        $tous = ['free', ...$this->atelier];

        foreach ($this->features as $feature => $inclus) {
            foreach ($tous as $cle) {
                $this->setFlag($cle, $feature, in_array($cle, $inclus, true));
            }
        }
    }

    private function setFlag(string $cle, string $feature, bool $val): void
    {
        $row = DB::table('niveaux_config')->where('cle', $cle)->first();
        if (! $row) {
            return;
        }
        $config = is_string($row->config) ? (json_decode($row->config, true) ?: []) : (array) $row->config;
        $config[$feature] = $val;
        DB::table('niveaux_config')->where('cle', $cle)->update([
            'config'     => json_encode($config, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        $tous = ['free', ...$this->atelier];
        foreach (array_keys($this->features) as $feature) {
            foreach ($tous as $cle) {
                $row = DB::table('niveaux_config')->where('cle', $cle)->first();
                if (! $row) {
                    continue;
                }
                $config = is_string($row->config) ? (json_decode($row->config, true) ?: []) : (array) $row->config;
                unset($config[$feature]);
                DB::table('niveaux_config')->where('cle', $cle)->update([
                    'config'     => json_encode($config, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
