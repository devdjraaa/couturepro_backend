<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Alignement des plans Designer sur la maquette officielle (direction, 16/07/2026 —
// cf. docs/PLANS_OFFICIELS_DESIGNER.md) : prix + labels (Master→Studio, en LABEL seulement,
// la `cle` reste inchangée car référencée par les abonnements) + quotas.
// Idempotent : on fait un MERGE de config (on écrase les clés officielles, on garde le reste).
return new class extends Migration
{
    // Quotas officiels par « famille » de plan.
    private array $quotas = [
        'free' => [
            'label' => 'Gratuit', 'prix' => 0,
            'desc'  => "Testez l'application sans limite de durée, sans engagement.",
            'cfg'   => [
                'max_clients_par_mois' => 10, 'max_commandes_par_mois' => null,
                'max_creations_vitrine' => 5, 'max_patrons' => 0, 'patrons_payants' => false,
                'max_assistants' => 0, 'max_membres' => 0,
                'multi_ateliers' => false, 'max_sous_ateliers' => 0,
                'sauvegarde_auto' => false, 'max_factures_par_mois' => 10,
                'commission_vitrine' => 15,
            ],
        ],
        'atelier' => [
            'label' => 'Atelier', 'prix_m' => 2500, 'prix_a' => 25000,
            'desc'  => 'Pour le styliste qui veut grandir et professionnaliser son studio.',
            'cfg'   => [
                'max_clients_par_mois' => 75, 'max_commandes_par_mois' => 75,
                'max_creations_vitrine' => 25, 'max_patrons' => 20, 'patrons_payants' => true,
                'max_assistants' => 1, 'max_membres' => 1,
                'multi_ateliers' => false, 'max_sous_ateliers' => 0,
                'sauvegarde_auto' => true, 'max_factures_par_mois' => null,
                'commission_vitrine' => 0,
            ],
        ],
        'studio' => [
            'label' => 'Studio', 'prix_m' => 5000, 'prix_a' => 50000, 'cle_base' => 'master',
            'desc'  => "Pour l'entreprise qui tourne à plein régime.",
            'cfg'   => [
                'max_clients_par_mois' => null, 'max_commandes_par_mois' => null,
                'max_creations_vitrine' => 50, 'max_patrons' => 50, 'patrons_payants' => true,
                'max_assistants' => 3, 'max_membres' => 3,
                'multi_ateliers' => true, 'max_sous_ateliers' => 7,
                'sauvegarde_auto' => true, 'max_factures_par_mois' => null,
                'commission_vitrine' => 0,
            ],
        ],
    ];

    public function up(): void
    {
        // free (plan unique)
        $this->appliquer('free', $this->quotas['free']['label'], $this->quotas['free']['prix'],
            $this->quotas['free']['desc'], $this->quotas['free']['cfg']);

        // atelier (cle = atelier_mensuel / atelier_annuel)
        $a = $this->quotas['atelier'];
        $this->appliquer('atelier_mensuel', "{$a['label']} Mensuel", $a['prix_m'], $a['desc'], $a['cfg']);
        $this->appliquer('atelier_annuel',  "{$a['label']} Annuel",  $a['prix_a'], $a['desc'], $a['cfg']);

        // studio (cle historique = master_mensuel / master_annuel ; on ne change QUE le label)
        $s = $this->quotas['studio'];
        $this->appliquer('master_mensuel', "{$s['label']} Mensuel", $s['prix_m'], $s['desc'], $s['cfg']);
        $this->appliquer('master_annuel',  "{$s['label']} Annuel",  $s['prix_a'], $s['desc'], $s['cfg']);
    }

    private function appliquer(string $cle, string $label, int $prix, string $desc, array $cfgOfficiel): void
    {
        $row = DB::table('niveaux_config')->where('cle', $cle)->first();
        if (! $row) {
            return; // plan absent (env différent) → on ne crée rien ici
        }

        $config = $row->config;
        if (is_string($config)) {
            $config = json_decode($config, true) ?: [];
        }
        $config = is_array($config) ? array_merge($config, $cfgOfficiel) : $cfgOfficiel;

        DB::table('niveaux_config')->where('cle', $cle)->update([
            'label'             => $label,
            'prix_xof'          => $prix,
            'description_courte' => $desc,
            'config'            => json_encode($config, JSON_UNESCAPED_UNICODE),
            'updated_at'        => now(),
        ]);
    }

    public function down(): void
    {
        // Restauration des prix/labels d'avant l'alignement (les quotas ne sont pas rétablis).
        foreach ([
            ['free', 'Free', 0],
            ['atelier_mensuel', 'Atelier Mensuel', 1200],
            ['atelier_annuel', 'Atelier Annuel', 12000],
            ['master_mensuel', 'Master Mensuel', 2500],
            ['master_annuel', 'Master Annuel', 25000],
        ] as [$cle, $label, $prix]) {
            DB::table('niveaux_config')->where('cle', $cle)->update([
                'label' => $label, 'prix_xof' => $prix, 'updated_at' => now(),
            ]);
        }
    }
};
