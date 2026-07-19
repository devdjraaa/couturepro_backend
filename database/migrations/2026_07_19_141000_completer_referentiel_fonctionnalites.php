<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * S02A-28 — Complète le référentiel `fonctionnalites` (libellés lisibles).
 *
 * Les plans actifs utilisent 40 clés de configuration, mais seules 17 avaient un
 * libellé : les 23 fonctionnalités ajoutées depuis (vitrine, facturation, studio…)
 * n'en avaient aucun. Conséquence : les messages de blocage et l'écran d'admin
 * affichaient la clé brute (« videos_presentation ») au lieu d'un libellé.
 *
 * Idempotent : relançable sans risque, ne modifie pas les libellés existants.
 */
return new class extends Migration
{
    /** @return array<int, array{0:string,1:string,2:string,3:string,4:?string,5:string}> */
    private function referentiel(): array
    {
        // [ clé, label, description, type, unité, catégorie ]
        return [
            ['visible_galerie', 'Visibilité dans la galerie', 'Le créateur apparaît dans la galerie publique de la vitrine', 'booleen', null, 'module'],
            ['max_creations_vitrine', 'Créations publiées en vitrine', 'Nombre de créations visibles publiquement', 'numerique', 'créations', 'module'],
            ['publications_par_periode', 'Publications par période', 'Actes de publication vitrine autorisés sur la période', 'numerique', 'publications', 'module'],
            ['max_commandes_par_mois', 'Commandes par mois', 'Nombre de commandes enregistrables par mois', 'numerique', 'commandes/mois', 'clients_commandes'],
            ['max_clients_factures_periode', 'Clients facturés par période', 'Nombre de clients distincts facturables sur la période', 'numerique', 'clients', 'clients_commandes'],
            ['facturation', 'Module facturation', 'Émission de devis et de factures', 'booleen', null, 'module'],
            ['facturation_normalisee', 'Facturation normalisée', 'Factures normalisées conformes à la DGI (e-MECeF)', 'booleen', null, 'module'],
            ['facture_personnalisee', 'Facture personnalisée', 'Logo, références IFU / RCCM et mise en page professionnelle', 'booleen', null, 'communication'],
            ['devis_vitrine', 'Demande de devis en ligne', 'Les visiteurs peuvent demander un devis depuis la vitrine', 'booleen', null, 'communication'],
            ['lookbook_pdf', 'Lookbook PDF', 'Catalogue PDF partageable de vos collections', 'booleen', null, 'module'],
            ['rapport_mensuel', 'Rapport mensuel', 'Rapport PDF mensuel d\'activité et d\'encaissements', 'booleen', null, 'module'],
            ['export_groupe', 'Export groupé', 'Export groupé des mesures, collections et patrons', 'booleen', null, 'module'],
            ['badge_designer_pro', 'Badge Designer Pro', 'Badge de crédibilité affiché sur votre profil public', 'booleen', null, 'module'],
            ['backup_cloud', 'Sauvegarde cloud', 'Sauvegarde automatique de vos données dans le cloud', 'booleen', null, 'stockage'],
            ['liste_attente', 'Liste d\'attente clients', 'Gestion d\'une file d\'attente quand la demande dépasse la capacité', 'booleen', null, 'module'],
            ['simulateur_revenus', 'Simulateur de revenus', 'Projections d\'activité et de chiffre d\'affaires', 'booleen', null, 'module'],
            ['annonce_collection', 'Annonce de collection', 'Mise en avant d\'une nouvelle collection sur la vitrine', 'booleen', null, 'communication'],
            ['videos_presentation', 'Vidéos de présentation', 'Présentation de votre savoir-faire en vidéo sur votre profil', 'booleen', null, 'communication'],
            ['sponsorisation', 'Mise en avant sponsorisée', 'Remonter en tête de l\'annuaire des créateurs', 'booleen', null, 'module'],
            ['patrons_payants', 'Vente de patrons', 'Vendre vos patrons en téléchargement sur la vitrine', 'booleen', null, 'module'],
            ['max_patrons', 'Patrons publiables', 'Nombre de patrons publiables', 'numerique', 'patrons', 'module'],
            ['commission_vitrine', 'Commission vitrine', 'Commission prélevée sur les ventes réalisées via la vitrine', 'numerique', '%', 'module'],
            ['fidelite_avancee', 'Fidélité avancée', 'Paliers de fidélité et récompenses', 'booleen', null, 'fidelite'],
        ];
    }

    public function up(): void
    {
        $ordre = 100;
        foreach ($this->referentiel() as [$cle, $label, $description, $type, $unite, $categorie]) {
            // updateOrInsert : ne touche pas aux libellés déjà personnalisés en base.
            if (DB::table('fonctionnalites')->where('cle', $cle)->exists()) {
                continue;
            }

            DB::table('fonctionnalites')->insert([
                'cle'             => $cle,
                'label'           => $label,
                'description'     => $description,
                'type'            => $type,
                'unite'           => $unite,
                'categorie'       => $categorie,
                'valeur_defaut'   => $type === 'booleen' ? '0' : null,
                'is_actif'        => true,
                'ordre_affichage' => $ordre++,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }
    }

    public function down(): void
    {
        $cles = array_column($this->referentiel(), 0);
        DB::table('fonctionnalites')->whereIn('cle', $cles)->delete();
    }
};
