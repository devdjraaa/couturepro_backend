<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VitrineSetting extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'vitrine_settings';

    protected $fillable = ['cle', 'valeur'];

    protected $casts = ['valeur' => 'array'];

    /**
     * Offres de sponsorisation (mise en avant vitrine), config-driven et
     * éditables depuis l'admin. Valeurs par défaut si rien n'est configuré.
     */
    public static function sponsorisation(): array
    {
        $cfg = static::where('cle', 'sponsorisation')->value('valeur');

        return $cfg ?: [
            'actif'  => true,
            'offres' => [
                ['jours' => 7,  'prix' => 1500],
                ['jours' => 15, 'prix' => 2500],
                ['jours' => 30, 'prix' => 4500],
            ],
        ];
    }

    /**
     * Paliers du programme de fidélité (Bronze / Argent / Or / Platine).
     *
     * Étaient CODÉS EN DUR dans le contrôleur : impossible de recalibrer sans
     * redéploiement. Or le constat du 20/07 montre que le programme est
     * inatteignable en l'état (375 points générés au total pour un premier palier
     * à 5 000) — la direction doit pouvoir ajuster elle-même.
     */
    public static function paliersFidelite(): array
    {
        $cfg = static::where('cle', 'paliers_fidelite')->value('valeur');

        return $cfg ?: [
            ['cle' => 'bronze',  'nom' => 'Bronze',  'seuil' => 0],
            ['cle' => 'argent',  'nom' => 'Argent',  'seuil' => 5000],
            ['cle' => 'or',      'nom' => 'Or',      'seuil' => 20000],
            ['cle' => 'platine', 'nom' => 'Platine', 'seuil' => 50000],
        ];
    }

    /**
     * S08C-30 — Moyens de paiement proposés en facturation (devis/factures).
     *
     * Décision direction : pour la première version, **FedaPay uniquement**, même si
     * l'intégration n'est pas finalisée — la structure doit rester évolutive.
     *
     * Remplace la liste qui était EN DUR côté front (`wave`, `om`, `especes`,
     * `virement`, `autre`) : ajouter un moyen ne demande plus de redéploiement.
     *
     * ⚠️ Périmètre : la FACTURATION uniquement. La caisse et les commandes ont
     * leur propre liste (`especes` / `mobile_money` / `virement`) et NE doivent
     * pas consommer celle-ci : elles enregistrent comment le client a réellement
     * payé sur place. Les brancher sur « FedaPay uniquement » supprimerait
     * l'encaissement en espèces, qui est le cas majoritaire des ateliers.
     * Unifier les deux notions est une décision produit, pas un nettoyage.
     */
    public static function moyensPaiement(): array
    {
        $cfg = static::where('cle', 'moyens_paiement')->value('valeur');

        return $cfg ?: [
            ['cle' => 'fedapay', 'label' => 'FedaPay', 'actif' => true, 'defaut' => true],
        ];
    }

    /**
     * REL-2 / Pt 125 — Titres et descriptions par page servis aux ROBOTS
     * (pré-rendu SEO). Éditables en admin ; défauts alignés sur les pages
     * réelles de la vitrine.
     */
    public static function seoPages(): array
    {
        $cfg = static::where('cle', 'seo_pages')->value('valeur');

        return is_array($cfg) && $cfg ? $cfg : [
            '/'                  => ['titre' => 'Gextimo — La marketplace des créateurs de mode africains', 'description' => 'Trouvez les meilleurs designers et tailleurs africains. Commandez des tenues sur mesure, suivez vos commandes en temps réel.'],
            '/createurs'         => ['titre' => 'Créateurs et ateliers de mode africaine | Gextimo', 'description' => 'Parcourez les créateurs, stylistes et tailleurs référencés sur Gextimo : spécialités, villes, avis clients et créations.'],
            '/artisans'          => ['titre' => 'Artisans de la mode | Gextimo', 'description' => 'Les artisans partenaires de Gextimo : couture, broderie, accessoires.'],
            '/qui-sommes-nous'   => ['titre' => 'Qui sommes-nous | Gextimo', 'description' => 'Gextimo, par Novafriq : la plateforme qui connecte créateurs, artisans et clients de la mode africaine.'],
            '/partenaires'       => ['titre' => 'Partenaires | Gextimo', 'description' => 'Ils accompagnent le développement de la mode africaine avec Gextimo.'],
            '/premium'           => ['titre' => 'Offres et abonnements | Gextimo', 'description' => 'Choisissez la formule adaptée à votre atelier : publication en vitrine, outils de gestion, visibilité renforcée.'],
            '/mise-en-avant'     => ['titre' => 'Mise en avant sponsorisée | Gextimo', 'description' => 'Renforcez la visibilité de votre atelier sur la vitrine Gextimo.'],
            '/aide'              => ['titre' => 'Aide et support | Gextimo', 'description' => 'Questions fréquentes et assistance Gextimo.'],
            '/espace-client'     => ['titre' => 'Espace client | Gextimo', 'description' => 'Suivez vos commandes, laissez des avis et gérez vos créateurs suivis.'],
            '/mentions-legales'  => ['titre' => 'Mentions légales | Gextimo', 'description' => 'Mentions légales de la plateforme Gextimo, éditée par Novafriq.'],
            '/confidentialite'   => ['titre' => 'Politique de confidentialité | Gextimo', 'description' => 'Comment Gextimo collecte, utilise et protège vos données personnelles.'],
            '/cgu'               => ['titre' => 'Conditions générales d\'utilisation | Gextimo', 'description' => 'Les conditions d\'utilisation de la plateforme Gextimo.'],
            '/cgv'               => ['titre' => 'Conditions générales de vente | Gextimo', 'description' => 'Les conditions de vente applicables sur Gextimo.'],
            '/cookies'           => ['titre' => 'Politique cookies | Gextimo', 'description' => 'L\'usage des cookies et traceurs sur Gextimo, et vos choix.'],
        ];
    }

    /**
     * Pt 10 (lot 2) — Types de pièces justificatives acceptées pour la
     * vérification Designer. Éditable en admin : ajouter un type ne demande
     * pas de redéploiement.
     */
    public static function typesDocumentVerification(): array
    {
        $cfg = static::where('cle', 'types_document_verification')->value('valeur');

        return is_array($cfg) && $cfg ? $cfg : [
            ['cle' => 'carte_identite',   'label' => "Carte d'identité / passeport"],
            ['cle' => 'registre_commerce','label' => 'Registre de commerce (RCCM)'],
            ['cle' => 'ifu',              'label' => 'Carte IFU'],
            ['cle' => 'justif_domicile',  'label' => 'Justificatif de domicile'],
            ['cle' => 'autre',            'label' => 'Autre document'],
        ];
    }

    /**
     * Coordonnées officielles de la marque, utilisées dans les documents et
     * partages sortants (PDF de mesures, message WhatsApp). Éditables en admin :
     * un changement de numéro ne doit pas demander un redéploiement.
     */
    public static function coordonnees(): array
    {
        $cfg = static::where('cle', 'coordonnees')->value('valeur');

        return array_merge([
            'marque'    => 'Gextimo',
            'site'      => 'www.gextimo.novafriq.africa',
            'telephone' => '+229 01 91 47 96 28',
        ], is_array($cfg) ? $cfg : []);
    }

    /**
     * Identité légale de la société, injectée dans les 11 pages juridiques.
     *
     * Ces valeurs étaient écrites en clair dans les fichiers de traduction sous
     * la forme « [NUMÉRO RCCM : à compléter après immatriculation] » — et donc
     * PUBLIÉES telles quelles sur les CGU, les mentions légales et huit autres
     * pages. Un crochet « à compléter » sur une page juridique est pire qu'une
     * absence : il donne à lire que la société n'est pas immatriculée.
     *
     * Elles vivent désormais ici, vides par défaut : tant qu'une valeur n'est
     * pas renseignée, la ligne qui la porte DISPARAÎT côté vitrine au lieu
     * d'afficher un gabarit. Le jour de l'immatriculation, la direction saisit
     * les numéros en admin — sans redéploiement et sans passer par un
     * développeur.
     */
    public static function identiteLegale(): array
    {
        $cfg = static::where('cle', 'identite_legale')->value('valeur');

        return array_merge([
            'rccm'                => '',
            'ifu'                 => '',
            'apdp_deliberation'   => '',
            'date_entree_vigueur' => '',
            'date_maj'            => '',
        ], is_array($cfg) ? $cfg : []);
    }

    /**
     * Makila — horaires de présence de l'ÉQUIPE HUMAINE (direction, 20/07).
     * Le badge « hors ligne » ne concerne que la disponibilité d'un humain :
     * Makila, lui, répond 24h/24 sans interruption. Bascule automatique sur
     * l'heure de Cotonou, réglable en admin.
     */
    public static function equipeHoraires(): array
    {
        $cfg = static::where('cle', 'equipe_horaires')->value('valeur');

        return array_merge([
            'actif'  => true,
            'debut'  => 8,    // heure locale de reprise
            'fin'    => 18,   // heure locale de fin
            'fuseau' => 'Africa/Porto-Novo',
        ], is_array($cfg) ? $cfg : []);
    }

    /** L'équipe humaine est-elle en ligne en ce moment ? */
    public static function equipeEnLigne(): bool
    {
        $h = static::equipeHoraires();
        if (! ($h['actif'] ?? true)) {
            return true;   // indicateur désactivé = ne jamais afficher « hors ligne »
        }
        $heure = (int) now($h['fuseau'])->format('G');

        return $heure >= (int) $h['debut'] && $heure < (int) $h['fin'];
    }

    /**
     * VASAT — second produit du groupe (directive direction 20/07) : présent sur
     * le site mais invisible au public, derrière un mot de passe. Le hash est posé
     * à la PREMIÈRE saisie (même principe TOFU que le tracker de suivi), puis
     * modifiable via l'admin.
     */
    public static function vasat(): array
    {
        $cfg = static::where('cle', 'vasat')->value('valeur');

        return array_merge(['actif' => true, 'mdp_hash' => null], is_array($cfg) ? $cfg : []);
    }

    /**
     * Avis v2 (décisions direction 20/07) — réglages de modération, éditables en
     * admin. `mots_bannis` démarre VIDE : la direction précise que la liste
     * « s'enrichit progressivement avec l'usage réel » — elle se remplit depuis
     * l'admin, pas depuis le code.
     */
    public static function moderationAvis(): array
    {
        $cfg = static::where('cle', 'moderation_avis')->value('valeur');

        return array_merge([
            'max_avis_par_jour'  => 5,     // décision 8 : anti-spam par compte
            'seuil_signalements' => 3,     // décision 7 : mise en file standard
            'motifs_graves'      => ['contenu_illegal', 'insulte', 'discrimination'],
            'mots_bannis'        => [],
        ], is_array($cfg) ? $cfg : []);
    }

    /**
     * CLI-1 — Journal des mises à jour (« Quoi de neuf »).
     *
     * Il n'existait qu'une **ligne de texte** dans une variable d'environnement
     * (`APP_UPDATE_NOTE`), affichée par la fenêtre de mise à jour puis perdue :
     * aucun historique, et rien de consultable après coup. Un professionnel qui
     * fermait la fenêtre ne pouvait plus jamais savoir ce qui avait changé.
     *
     * Éditable en admin, parce que les publications sont AUTOMATIQUES au push :
     * exiger un déploiement pour décrire une version reviendrait à ne jamais la
     * décrire.
     *
     * Chaque entrée : `version`, `date`, `titre`, `type`, `lignes[]`. La liste
     * est rendue de la plus récente à la plus ancienne, tri fait ici pour que
     * l'ordre de saisie en admin n'ait aucune importance.
     */
    public static function journalMaj(): array
    {
        $cfg = static::where('cle', 'journal_maj')->value('valeur');
        $entrees = is_array($cfg) ? $cfg : [];

        // Tri par date, puis par VERSION à date égale. Le second critère n'est
        // pas théorique : quatre publications dans la même journée est le rythme
        // observé. Sans lui, l'ordre d'affichage ne tiendrait qu'à l'ordre de
        // saisie en admin, et la pastille « du nouveau » — qui regarde la
        // première entrée — pourrait pointer une version plus ancienne.
        usort($entrees, function ($a, $b) {
            $parDate = strcmp($b['date'] ?? '', $a['date'] ?? '');

            return $parDate !== 0
                ? $parDate
                : version_compare($b['version'] ?? '', $a['version'] ?? '');
        });

        return $entrees;
    }

    /**
     * CLI-2 — Catégories de « Gextimo Infos ».
     *
     * Éditables en admin : la direction ajoutera des catégories au fil des
     * campagnes (recrutement, partenariat…) et ne doit pas dépendre d'un
     * déploiement pour cela. La couleur et l'icône voyagent avec la catégorie
     * pour que l'écran n'ait aucune correspondance codée en dur.
     *
     * `cle` est ce qui est stocké en base : la renommer casserait les messages
     * déjà diffusés — seul `label` se change librement.
     */
    public static function categoriesInfos(): array
    {
        $cfg = static::where('cle', 'categories_infos')->value('valeur');

        return is_array($cfg) && $cfg ? $cfg : [
            ['cle' => 'annonce',   'label' => 'Annonce',    'couleur' => '#D00B0B', 'icone' => 'megaphone'],
            ['cle' => 'nouveaute', 'label' => 'Nouveauté',  'couleur' => '#0F766E', 'icone' => 'sparkles'],
            ['cle' => 'astuce',    'label' => 'Astuce',     'couleur' => '#B45309', 'icone' => 'lightbulb'],
            ['cle' => 'promo',     'label' => 'Promotion',  'couleur' => '#7C3AED', 'icone' => 'percent'],
            ['cle' => 'alerte',    'label' => 'Alerte',     'couleur' => '#DC2626', 'icone' => 'alert-triangle'],
            ['cle' => 'evenement', 'label' => 'Événement',  'couleur' => '#2563EB', 'icone' => 'calendar'],
            ['cle' => 'formation', 'label' => 'Formation',  'couleur' => '#059669', 'icone' => 'graduation-cap'],
            ['cle' => 'securite',  'label' => 'Sécurité',   'couleur' => '#EA580C', 'icone' => 'shield-alert'],
        ];
    }

    /**
     * CLI-3 — Compte à rebours de lancement (22 août).
     *
     * Deux composants d'un même réglage : une BANDE discrète qui s'affiche à
     * partir de J-30 et se masque d'elle-même une fois l'échéance passée, et un
     * CHRONO plein écran le jour J.
     *
     * Tout est éditable en admin — date, heure, textes, couleurs, seuil de
     * déclenchement — parce que ce compte à rebours ne servira pas qu'au 22
     * août : la direction voudra le rejouer pour d'autres annonces. Une date
     * écrite en dur aurait obligé à redéployer à chaque fois.
     *
     * `actif` à false par défaut : rien ne s'affiche tant que la direction n'a
     * pas décidé, plutôt qu'un compte à rebours surgissant chez les utilisateurs
     * au premier déploiement.
     */
    public static function compteARebours(): array
    {
        $cfg = static::where('cle', 'compte_a_rebours')->value('valeur');

        return array_merge([
            'actif'           => false,
            'date_cible'      => '2026-08-22 08:00',   // heure de Cotonou
            'jours_avant'     => 30,                   // seuil d'apparition de la bande
            'titre'           => 'Gextimo arrive',
            'texte_bande'     => 'Plus que {{jours}} jours avant le lancement.',
            'texte_jour_j'    => "C'est aujourd'hui !",
            'couleur'         => '#D00B0B',
            'lien'            => null,                 // facultatif : « en savoir plus »
            'chrono_jour_j'   => true,                 // plein écran le jour J
        ], is_array($cfg) ? $cfg : []);
    }

    /**
     * ANN-6 — Tarifs du Boost d'annonce (mise en avant payante), config-driven
     * et éditables depuis l'admin. La publication d'une annonce reste gratuite ;
     * seul le Boost est payant. `diffusions_par_jour` = nombre de passages
     * quotidiens pendant la durée du Boost.
     */
    public static function boostAnnonce(): array
    {
        $cfg = static::where('cle', 'boost_annonce')->value('valeur');

        return $cfg ?: [
            'actif'               => true,
            'diffusions_par_jour' => 3,
            'offres'              => [
                ['jours' => 1, 'prix' => 100],
                ['jours' => 3, 'prix' => 200],
                ['jours' => 7, 'prix' => 300],
            ],
        ];
    }

    /**
     * Point 57 — Catalogue d'événements dynamiques (célébrations), config-driven
     * et éditable depuis l'admin (clé `evenements_celebration`). Valeurs par défaut
     * FACTUELLES si rien n'est configuré : jours fériés du Bénin + fêtes religieuses
     * mobiles (dates explicites par année, à ajuster chaque année) + un gabarit
     * d'anniversaire client + un gabarit interne Gextimo (désactivé jusqu'à ce que
     * l'admin renseigne la vraie date).
     *
     * 5 familles : fixe (MM-JJ), lunaire (dates explicites), gextimo (interne),
     * utilisateur (anniversaire calculé), marketing (fenêtre datée).
     */
    public static function evenementsCelebration(): array
    {
        $cfg = static::where('cle', 'evenements_celebration')->value('valeur');
        if ($cfg) {
            return $cfg;
        }

        // Fêtes nationales fixes du Bénin (jours fériés officiels).
        $fixes = [
            ['code' => 'nouvel_an',    'date_fixe' => '01-01', 'anim' => 'confettis', 'fr' => 'Bonne année !',            'en' => 'Happy New Year!',        'mfr' => 'Toute l\'équipe Gextimo vous souhaite une excellente année.', 'men' => 'The Gextimo team wishes you a wonderful year.'],
            ['code' => 'fete_vodoun',  'date_fixe' => '01-10', 'anim' => 'etoiles',   'fr' => 'Fête des religions traditionnelles', 'en' => 'Traditional Religions Day', 'mfr' => 'Bonne fête du Vodoun à toute la communauté.', 'men' => 'Happy Vodun Festival to the whole community.'],
            ['code' => 'fete_travail', 'date_fixe' => '05-01', 'anim' => 'aucune',    'fr' => 'Bonne fête du travail',   'en' => 'Happy Labour Day',       'mfr' => 'Hommage à toutes les artisanes et à tous les artisans.', 'men' => 'A tribute to all craftspeople.'],
            ['code' => 'independance', 'date_fixe' => '08-01', 'anim' => 'confettis', 'fr' => 'Joyeuse fête de l\'Indépendance', 'en' => 'Happy Independence Day', 'mfr' => 'Bonne fête nationale du Bénin.', 'men' => 'Happy Benin Independence Day.'],
            ['code' => 'assomption',   'date_fixe' => '08-15', 'anim' => 'aucune',    'fr' => 'Bonne fête de l\'Assomption', 'en' => 'Happy Assumption Day',  'mfr' => '', 'men' => ''],
            ['code' => 'toussaint',    'date_fixe' => '11-01', 'anim' => 'aucune',    'fr' => 'Toussaint',               'en' => 'All Saints\' Day',       'mfr' => '', 'men' => ''],
            ['code' => 'noel',         'date_fixe' => '12-25', 'anim' => 'neige',     'fr' => 'Joyeux Noël !',           'en' => 'Merry Christmas!',       'mfr' => 'De belles fêtes de fin d\'année à vous et vos proches.', 'men' => 'Season\'s greetings to you and your loved ones.'],
        ];

        $catalogue = [];
        foreach ($fixes as $f) {
            $catalogue[] = [
                'code' => $f['code'], 'type' => 'fixe', 'date_fixe' => $f['date_fixe'],
                'titre' => ['fr' => $f['fr'], 'en' => $f['en']],
                'message' => ['fr' => $f['mfr'], 'en' => $f['men']],
                'animation' => $f['anim'], 'couleur' => '#C4162A', 'image_url' => null,
                'priorite' => 0, 'cible' => 'tous', 'mode_affichage' => 'splash',
                'frequence_affichage' => 'quotidien', 'actif' => true,
            ];
        }

        // Fêtes religieuses mobiles (dates explicites — À AJUSTER CHAQUE ANNÉE côté admin).
        $catalogue[] = [
            'code' => 'aid_el_fitr', 'type' => 'lunaire', 'dates' => ['2026-03-20', '2027-03-10'],
            'titre' => ['fr' => 'Aïd el-Fitr moubarak', 'en' => 'Eid al-Fitr Mubarak'],
            'message' => ['fr' => 'Bonne fête à toute la communauté musulmane.', 'en' => 'Blessings to the whole Muslim community.'],
            'animation' => 'etoiles', 'couleur' => '#1F7A5A', 'image_url' => null,
            'priorite' => 0, 'cible' => 'tous', 'mode_affichage' => 'splash',
            'frequence_affichage' => 'quotidien', 'actif' => true,
        ];
        $catalogue[] = [
            'code' => 'tabaski', 'type' => 'lunaire', 'dates' => ['2026-05-27', '2027-05-17'],
            'titre' => ['fr' => 'Tabaski moubarak', 'en' => 'Eid al-Adha Mubarak'],
            'message' => ['fr' => 'Bonne fête de la Tabaski à toutes et à tous.', 'en' => 'Happy Eid al-Adha to everyone.'],
            'animation' => 'etoiles', 'couleur' => '#1F7A5A', 'image_url' => null,
            'priorite' => 0, 'cible' => 'tous', 'mode_affichage' => 'splash',
            'frequence_affichage' => 'quotidien', 'actif' => true,
        ];

        // Gabarit anniversaire client (famille « utilisateur », calculé) — {prenom} substitué.
        $catalogue[] = [
            'code' => 'anniversaire_client', 'type' => 'utilisateur',
            'titre' => ['fr' => 'Joyeux anniversaire {prenom} !', 'en' => 'Happy birthday {prenom}!'],
            'message' => ['fr' => 'Toute l\'équipe Gextimo vous souhaite une merveilleuse journée.', 'en' => 'The whole Gextimo team wishes you a wonderful day.'],
            'animation' => 'coeurs', 'couleur' => '#C4162A', 'image_url' => null,
            'priorite' => 0, 'cible' => 'clients', 'mode_affichage' => 'toast',
            'frequence_affichage' => 'quotidien', 'actif' => true,
        ];

        // Gabarit interne Gextimo (désactivé : l'admin renseigne la vraie date d'anniversaire).
        $catalogue[] = [
            'code' => 'anniversaire_gextimo', 'type' => 'gextimo', 'date_fixe' => null,
            'titre' => ['fr' => 'Gextimo fête son anniversaire', 'en' => 'Gextimo celebrates its birthday'],
            'message' => ['fr' => 'Merci de faire grandir la mode africaine avec nous.', 'en' => 'Thank you for growing African fashion with us.'],
            'animation' => 'confettis', 'couleur' => '#C4162A', 'image_url' => null,
            'priorite' => 0, 'cible' => 'tous', 'mode_affichage' => 'splash',
            'frequence_affichage' => 'quotidien', 'actif' => false,
        ];

        return $catalogue;
    }
}
