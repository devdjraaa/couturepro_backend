<?php

use App\Models\NiveauConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Ajoute les flags de fonctionnalités (RBAC par abonnement) au config de
     * chaque plan, s'ils n'existent pas déjà. Défaut = true : non destructif,
     * rien ne casse, mais chaque feature devient activable/désactivable par
     * plan depuis l'admin (PlansPage). getConfigEffective() retombe sur le live,
     * donc les abonnés existants héritent automatiquement de ces flags.
     */
    private array $flags = [
        'facturation'            => true, // module devis/facture/reçu
        'facturation_normalisee' => true, // normalisation DGI e-MECeF
        'devis_vitrine'          => true, // demandes de devis depuis la vitrine
        'sponsorisation'         => true, // achat de mise en avant
    ];

    public function up(): void
    {
        foreach (NiveauConfig::all() as $niveau) {
            $config = $niveau->config;
            if (is_string($config)) {
                $config = json_decode($config, true) ?? [];
            }
            $config = is_array($config) ? $config : [];

            foreach ($this->flags as $cle => $defaut) {
                if (! array_key_exists($cle, $config)) {
                    $config[$cle] = $defaut;
                }
            }

            $niveau->config = $config;
            $niveau->save();
        }
    }

    public function down(): void
    {
        foreach (NiveauConfig::all() as $niveau) {
            $config = $niveau->config;
            if (is_string($config)) {
                $config = json_decode($config, true) ?? [];
            }
            if (! is_array($config)) {
                continue;
            }

            foreach (array_keys($this->flags) as $cle) {
                unset($config[$cle]);
            }

            $niveau->config = $config;
            $niveau->save();
        }
    }
};
