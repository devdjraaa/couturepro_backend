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
     * Catégories de la galerie publique — ÉDITABLES depuis l'administration.
     *
     * Défauts issus d'une recherche des bonnes pratiques (marketplace ANKA,
     * référence de la mode africaine, + contexte couture sur mesure au Bénin),
     * pas inventés « au feeling ». La direction ajoute, renomme ou retire depuis
     * le back-office : chaque création porte alors la clé d'une de ces
     * catégories, et les filtres de la galerie s'appuient dessus.
     *
     * Une catégorie = { cle, actif, label:{fr,en} }. L'ordre du tableau est
     * l'ordre d'affichage des filtres.
     */
    public static function categoriesCreations(): array
    {
        $cfg = static::where('cle', 'categories_creations')->value('valeur');
        if (is_array($cfg) && $cfg !== []) {
            return $cfg;
        }

        return [
            ['cle' => 'robes',        'actif' => true, 'label' => ['fr' => 'Robes',                    'en' => 'Dresses']],
            ['cle' => 'ensembles',    'actif' => true, 'label' => ['fr' => 'Ensembles & tailleurs',    'en' => 'Sets & suits']],
            ['cle' => 'traditionnel', 'actif' => true, 'label' => ['fr' => 'Tenues traditionnelles',   'en' => 'Traditional wear']],
            ['cle' => 'ceremonie',    'actif' => true, 'label' => ['fr' => 'Cérémonie & mariage',       'en' => 'Ceremony & wedding']],
            ['cle' => 'hauts',        'actif' => true, 'label' => ['fr' => 'Hauts & chemises',          'en' => 'Tops & shirts']],
            ['cle' => 'bas',          'actif' => true, 'label' => ['fr' => 'Bas (jupes, pantalons)',    'en' => 'Bottoms']],
            ['cle' => 'vestes',       'actif' => true, 'label' => ['fr' => 'Vestes & manteaux',         'en' => 'Jackets & coats']],
            ['cle' => 'combinaisons', 'actif' => true, 'label' => ['fr' => 'Combinaisons',              'en' => 'Jumpsuits']],
            ['cle' => 'enfant',       'actif' => true, 'label' => ['fr' => 'Enfant',                    'en' => 'Kids']],
            ['cle' => 'accessoires',  'actif' => true, 'label' => ['fr' => 'Accessoires',               'en' => 'Accessories']],
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
    /**
     * Presentation de la page de tarifs.
     *
     * Tout ce qui est texte ou choix editorial vit ici, jamais dans le code :
     * quel plan porte le badge, la note de bas de grille, l'encart des options
     * complementaires. La direction change un mot ou deplace le badge sans
     * developpeur ni deploiement.
     *
     * Les textes sont bilingues. Une traduction vide retombe sur le francais —
     * mieux vaut un mot en francais qu'un blanc sur une page de tarifs.
     */
    public static function tarification(): array
    {
        $cfg = static::where('cle', 'tarification')->value('valeur');

        return array_replace_recursive([
            // Le plan mis en avant. Vide = aucun badge. La cle correspond au
            // palier (« atelier », « master »), pas a la variante mensuelle.
            'plan_populaire' => 'atelier',
            'badge_populaire' => ['fr' => 'Plan populaire', 'en' => 'Most popular'],

            // Le selecteur artisan / designer.
            'types_actif' => true,
            'type_artisan' => [
                'libelle' => ['fr' => 'Artisan', 'en' => 'Artisan'],
                'texte'   => ['fr' => 'Pour gérer votre atelier au quotidien.', 'en' => 'To run your workshop day to day.'],
            ],
            'type_designer' => [
                'libelle' => ['fr' => 'Designer', 'en' => 'Designer'],
                'texte'   => ['fr' => 'Pour exposer vos créations et vendre en ligne.', 'en' => 'To showcase your creations and sell online.'],
            ],

            // L'essai gratuit. C'est l'argument de vente le plus fort et il
            // n'apparaissait NULLE PART sur la page des tarifs : tout nouveau
            // compte reçoit d'emblée un plan PAYANT pendant cette durée
            // (Studio pour un designer, Atelier pour un artisan).
            //
            // `essai_jours` est la SOURCE UNIQUE : l'inscription la lit aussi.
            // Avant, la durée était écrite en dur à trois endroits du
            // contrôleur d'inscription — la changer demandait un déploiement, et
            // rien ne garantissait que la page annonce la même chose que ce que
            // le compte recevait vraiment.
            'essai_actif' => true,
            'essai_jours' => 14,
            'essai_titre' => ['fr' => '{jours} jours offerts', 'en' => '{jours} days free'],
            'essai_texte' => [
                'fr' => 'Chaque nouveau compte démarre avec {jours} jours d\'accès complet au plan payant, sans rien payer et sans moyen de paiement à renseigner. À la fin de l\'essai, le compte bascule sur le plan Gratuit si aucun paiement n\'est fait.',
                'en' => 'Every new account starts with {jours} days of full access to the paid plan, with nothing to pay and no payment details to enter. When the trial ends, the account moves to the Free plan unless a payment is made.',
            ],

            // ── Libellés des fonctionnalités ─────────────────────────────
            // Le texte de chaque ligne d'un plan, éditable ici. Il était figé
            // dans les fichiers de traduction : changer « Photos VIP » en
            // « Photos mises en avant » demandait un développeur et un
            // déploiement, pour un mot.
            //
            // `{n}` est remplacé par la valeur réelle du plan. La direction
            // saisit 7 sous-ateliers, la ligne dit « Jusqu'à 7 ateliers » —
            // sur la vitrine ET dans l'application, sans y toucher deux fois.
            //
            // Une entrée vide retombe sur le texte livré : un libellé effacé
            // par erreur ne doit pas laisser un blanc sur une page de tarifs.
            'libelles' => [
                'creations'          => ['fr' => '{n} créations en vitrine',              'en' => '{n} showcase creations'],
                'creations_illimite' => ['fr' => 'Créations en vitrine illimitées',       'en' => 'Unlimited showcase creations'],
                'clients'            => ['fr' => '{n} clients par mois',                  'en' => '{n} clients per month'],
                'clients_illimite'   => ['fr' => 'Clients illimités',                     'en' => 'Unlimited clients'],
                'equipe'             => ['fr' => "Équipe jusqu'à {n} membres",            'en' => 'Team up to {n} members'],
                'equipe_illimite'    => ['fr' => "Membres d'équipe illimités",            'en' => 'Unlimited team members'],
                'galerie_oui'        => ['fr' => 'Vos créations visibles dans la galerie publique', 'en' => 'Your creations visible in the public gallery'],
                'galerie_non'        => ['fr' => 'Profil visible par lien direct, absent de la galerie publique', 'en' => 'Profile visible by direct link, not listed in the gallery'],
                'pdf'                => ['fr' => 'Factures et devis en PDF',              'en' => 'PDF invoices and quotes'],
                'photos_vip'         => ['fr' => 'Photos mises en avant dans la galerie', 'en' => 'Featured photos in the gallery'],
                'photos_vip_quota'   => ['fr' => '{n} photos mises en avant par mois',    'en' => '{n} featured photos per month'],
                'factures_wa'        => ['fr' => 'Envoi des factures par WhatsApp',       'en' => 'Send invoices via WhatsApp'],
                'factures_wa_quota'  => ['fr' => '{n} factures par WhatsApp / mois',      'en' => '{n} WhatsApp invoices / month'],
                'caisse'             => ['fr' => 'Caisse : encaissements et suivi du solde', 'en' => 'Cash register: payments and balance tracking'],
                'dgi'                => ['fr' => 'Facture normalisée DGI',                'en' => 'DGI-compliant invoice'],
                'multi'              => ['fr' => 'Plusieurs ateliers sous un même compte', 'en' => 'Several workshops under one account'],
                'multi_quota'        => ['fr' => "Jusqu'à {n} ateliers supplémentaires",  'en' => 'Up to {n} extra workshops'],
            ],

            // La note sous la grille.
            'note_actif' => true,
            'note' => [
                'fr' => 'Les caractéristiques et limites des plans peuvent être modifiées à tout moment sans préavis.',
                'en' => 'Plan features and limits may change at any time without notice.',
            ],

            // L'encart des options complementaires.
            'packs_actif' => true,
            'packs_titre' => ['fr' => 'Besoin de plus ?', 'en' => 'Need more?'],
            'packs_texte' => [
                'fr' => 'Des options complémentaires peuvent compléter votre offre : membres supplémentaires, quotas augmentés, extensions. Écrivez-nous pour en discuter.',
                'en' => 'Add-ons can extend your plan: extra team members, higher quotas, additional options. Get in touch to discuss.',
            ],
            'packs_bouton' => ['fr' => 'Contacter le service', 'en' => 'Contact us'],
            'packs_lien'   => '/support',
        ], is_array($cfg) ? $cfg : []);
    }

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
     * CLI-1 — Devise par pays.
     *
     * La colonne `devise` existait déjà sur les paramètres d'atelier, avec
     * `XOF` en dur par défaut, et **aucun pays n'était jamais détecté** : un
     * atelier ghanéen ou nigérian se voyait attribuer le franc CFA à
     * l'inscription. Pire, dix-neuf écrans écrivaient « FCFA » en clair sans
     * passer par le réglage — le changer n'aurait donc rien changé à l'écran.
     *
     * La correspondance vit ICI et non côté écran : la devise détermine des
     * montants de facture et de paiement, pas une simple étiquette.
     *
     * `decimales` compte : le franc CFA ne se divise pas, le cedi et le naira
     * si. Formater un montant ghanéen sans décimale fausse la facture.
     *
     * Un pays absent de cette table retombe sur `defaut` — jamais d'erreur, et
     * la table s'enrichit depuis l'admin sans redéploiement.
     */
    public static function devisesParPays(): array
    {
        $cfg = static::where('cle', 'devises_par_pays')->value('valeur');

        return array_merge([
            'defaut' => ['devise' => 'XOF', 'symbole' => 'FCFA', 'decimales' => 0],
            'pays'   => [
                // Zone franc CFA (UEMOA + CEMAC) — le cas très majoritaire.
                'BJ' => 'XOF', 'BF' => 'XOF', 'CI' => 'XOF', 'GW' => 'XOF',
                'ML' => 'XOF', 'NE' => 'XOF', 'SN' => 'XOF', 'TG' => 'XOF',
                'CM' => 'XAF', 'CF' => 'XAF', 'TD' => 'XAF', 'CG' => 'XAF',
                'GA' => 'XAF', 'GQ' => 'XAF',
                // Hors zone franc
                'GH' => 'GHS', 'NG' => 'NGN', 'GN' => 'GNF', 'GM' => 'GMD',
                'LR' => 'LRD', 'SL' => 'SLE', 'CV' => 'CVE', 'MR' => 'MRU',
                'CD' => 'CDF', 'ST' => 'STN',
                'MA' => 'MAD', 'DZ' => 'DZD', 'TN' => 'TND', 'EG' => 'EGP',
                'FR' => 'EUR', 'BE' => 'EUR', 'US' => 'USD', 'CA' => 'CAD', 'GB' => 'GBP',
            ],
            'formats' => [
                'XOF' => ['symbole' => 'FCFA', 'decimales' => 0],
                'XAF' => ['symbole' => 'FCFA', 'decimales' => 0],
                'GHS' => ['symbole' => 'GH₵',  'decimales' => 2],
                'NGN' => ['symbole' => '₦',    'decimales' => 2],
                'GNF' => ['symbole' => 'FG',   'decimales' => 0],
                'GMD' => ['symbole' => 'D',    'decimales' => 2],
                'LRD' => ['symbole' => 'L$',   'decimales' => 2],
                'SLE' => ['symbole' => 'Le',   'decimales' => 2],
                'CVE' => ['symbole' => '$',    'decimales' => 0],
                'MRU' => ['symbole' => 'UM',   'decimales' => 2],
                'CDF' => ['symbole' => 'FC',   'decimales' => 0],
                'STN' => ['symbole' => 'Db',   'decimales' => 2],
                'MAD' => ['symbole' => 'DH',   'decimales' => 2],
                'DZD' => ['symbole' => 'DA',   'decimales' => 2],
                'TND' => ['symbole' => 'DT',   'decimales' => 3],
                'EGP' => ['symbole' => 'E£',   'decimales' => 2],
                'EUR' => ['symbole' => '€',    'decimales' => 2],
                'USD' => ['symbole' => '$',    'decimales' => 2],
                'CAD' => ['symbole' => 'C$',   'decimales' => 2],
                'GBP' => ['symbole' => '£',    'decimales' => 2],
            ],
        ], is_array($cfg) ? $cfg : []);
    }

    /**
     * Indicatifs téléphoniques → code pays, pour deviner le pays d'un atelier
     * à partir du seul numéro saisi à l'inscription.
     *
     * Les indicatifs les PLUS LONGS sont testés en premier : « +1 » (Amérique
     * du Nord) préfixe « +1868 » et bien d'autres ; comparer dans l'ordre
     * naturel attribuerait ces pays aux États-Unis.
     */
    public static function indicatifsPays(): array
    {
        $cfg = static::where('cle', 'indicatifs_pays')->value('valeur');

        return is_array($cfg) && $cfg ? $cfg : [
            '+229' => 'BJ', '+226' => 'BF', '+238' => 'CV', '+220' => 'GM',
            '+233' => 'GH', '+224' => 'GN', '+245' => 'GW', '+225' => 'CI',
            '+231' => 'LR', '+223' => 'ML', '+222' => 'MR', '+227' => 'NE',
            '+234' => 'NG', '+232' => 'SL', '+221' => 'SN', '+228' => 'TG',
            '+237' => 'CM', '+236' => 'CF', '+235' => 'TD', '+242' => 'CG',
            '+243' => 'CD', '+241' => 'GA', '+240' => 'GQ', '+239' => 'ST',
            '+212' => 'MA', '+213' => 'DZ', '+216' => 'TN', '+20' => 'EG',
            '+33' => 'FR', '+32' => 'BE', '+44' => 'GB', '+1' => 'US',
        ];
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
     * Veille opportunités — sources et mots-clés, ÉDITABLES sans redéploiement.
     *
     * L'ancienne veille passait par n8n avec des requêtes génériques en anglais :
     * elle remontait des marchés de Noël en France et des articles MSN, et
     * ratait l'essentiel — la Nuit du Kanvo Indigo au Bénin, par exemple.
     *
     * Google Actualités est interrogé en locale BÉNINOISE (gl=BJ, hl=fr) : il
     * couvre d'un coup toute la presse locale (La Nouvelle Tribune, Banouto,
     * 24h au Bénin, Matin Libre, Bénin Web TV…) sans avoir à brancher chaque
     * site un par un. Les requêtes ciblées `site:` complètent sur l'officiel.
     */
    /** Au-delà, chaque exécution paie une requête de plus pour un site de moins en moins productif. */
    private const MAX_SITES_FAVORIS = 12;

    /** On garde une mémoire plus large que ce qu'on interroge : un site peut redevenir productif. */
    private const MAX_MEMOIRE_FAVORIS = 60;

    public static function veilleSources(): array
    {
        // Sources écrites en toutes lettres (URL complètes) : réservé aux flux
        // qui ne sont pas des recherches Google Actualités.
        $cfg = static::where('cle', 'veille_sources')->value('valeur');
        if (is_array($cfg) && $cfg !== []) {
            return $cfg;
        }

        return array_map(
            fn ($q) => [
                'libelle' => $q,
                // hl/gl/ceid en Bénin : c'est ce qui fait remonter la presse locale.
                'url' => 'https://news.google.com/rss/search?q=' . rawurlencode($q)
                    . '&hl=fr&gl=BJ&ceid=BJ:fr',
            ],
            array_merge(static::veilleRecherches(), static::recherchesSitesFavoris()),
        );
    }

    /**
     * Les SITES QUI ONT DÉJÀ RAPPORTÉ, interrogés directement.
     *
     * Un site qui a publié une fois un article utile en publiera d'autres : la
     * presse béninoise spécialisée, les portails gouvernementaux, les organes
     * qui couvrent les salons. S'en remettre uniquement aux mots-clés revient
     * à espérer que le titre du prochain article contienne les bons termes —
     * et à rater tout ce qui est formulé autrement.
     *
     * Chaque favori est donc réinterrogé sur l'ensemble du vocabulaire du
     * métier, ce qui rattrape les articles dont le titre ne dit ni « Bénin »
     * ni le mot exact que nous suivions.
     *
     * Le domaine vient de la balise `<source url>` du flux : les liens, eux,
     * pointent tous vers news.google.com et ne disent rien de l'éditeur.
     */
    public static function recherchesSitesFavoris(): array
    {
        $favoris = static::sitesFavoris();
        if ($favoris === []) {
            return [];
        }

        // Les plus productifs d'abord, et pas au-delà : chaque favori est une
        // requête de plus à chaque exécution.
        uasort($favoris, fn ($a, $b) => ($b['succes'] ?? 0) <=> ($a['succes'] ?? 0));
        $retenus = array_slice(array_keys($favoris), 0, self::MAX_SITES_FAVORIS, true);

        $metier = array_slice(static::veilleMotsCles()['metier'] ?? [], 0, 8);
        if ($metier === []) {
            return [];
        }
        $ou = '(' . implode(' OR ', $metier) . ')';

        return array_map(fn ($domaine) => "site:{$domaine} {$ou}", $retenus);
    }

    /** Domaines ayant déjà produit un article retenu : domaine => {nom, succes, dernier}. */
    public static function sitesFavoris(): array
    {
        $cfg = static::where('cle', 'veille_sites_favoris')->value('valeur');

        return is_array($cfg) ? $cfg : [];
    }

    /**
     * Enregistre les domaines des articles retenus.
     *
     * On ne compte QUE les articles retenus : un site qui remonte du bruit à
     * chaque exécution deviendrait sinon le mieux classé de la liste, et ses
     * requêtes prendraient la place de celles qui rapportent.
     */
    public static function memoriserSitesFavoris(array $domaines): void
    {
        if ($domaines === []) {
            return;
        }

        $favoris = static::sitesFavoris();
        $jour = now('Africa/Porto-Novo')->toDateString();

        foreach ($domaines as $domaine => $nom) {
            $favoris[$domaine] = [
                'nom'     => mb_substr((string) ($nom ?: $domaine), 0, 120),
                'succes'  => (int) ($favoris[$domaine]['succes'] ?? 0) + 1,
                'dernier' => $jour,
            ];
        }

        uasort($favoris, fn ($a, $b) => ($b['succes'] ?? 0) <=> ($a['succes'] ?? 0));

        static::updateOrCreate(
            ['cle' => 'veille_sites_favoris'],
            ['valeur' => array_slice($favoris, 0, self::MAX_MEMOIRE_FAVORIS, true)],
        );
    }

    /**
     * Les termes de recherche, ÉDITABLES DEPUIS L'ADMINISTRATION.
     *
     * Ils étaient modifiables « en base », ce qui revenait à ne l'être pour
     * personne : il fallait une console. La direction peut désormais en ajouter
     * depuis l'écran de veille, sans déploiement ni intervention technique.
     *
     * On stocke le TERME, pas l'URL : personne n'a à connaître la forme des
     * adresses de Google Actualités pour enrichir la recherche.
     */
    public static function veilleRecherches(): array
    {
        $cfg = static::where('cle', 'veille_recherches')->value('valeur');
        if (is_array($cfg) && $cfg !== []) {
            return array_values(array_filter(array_map('trim', $cfg)));
        }

        return [
            // ── Cœur de métier, au Bénin ──
            'textile Bénin', 'mode Bénin', 'créateur mode Bénin', 'styliste Bénin',
            'artisanat Bénin', 'couture Bénin', 'atelier couture Bénin',
            'pagne tissé Bénin', 'kanvo Bénin', 'indigo Bénin', 'batik Bénin',
            'coton Bénin transformation', 'GDIZ textile', 'Glo-Djigbé textile',
            // ── Occasions à saisir ──
            'salon artisanat Bénin', 'concours mode Bénin', 'défilé mode Bénin',
            'foire artisanat Bénin', 'appel à projets Bénin artisanat',
            'financement PME Bénin', 'subvention artisan Bénin',
            'formation couture Bénin', 'exportation textile Bénin',
            // ── Sources officielles ──
            'site:pmepe.gouv.bj', 'site:gouv.bj textile', 'site:gouv.bj artisanat',
            // ── Élargissement régional, sans quitter le sujet ──
            "mode africaine Afrique de l'Ouest", 'textile UEMOA', 'mode Togo Bénin Niger',
        ];
    }

    /**
     * Mots-clés de pertinence. Le Bénin pèse le plus lourd : la veille doit
     * rester béninoise avant d'être africaine (demande direction).
     */
    public static function veilleMotsCles(): array
    {
        $cfg = static::where('cle', 'veille_mots_cles')->value('valeur');
        if (is_array($cfg) && $cfg !== []) {
            return $cfg;
        }

        return [
            'benin'   => ['bénin', 'benin', 'cotonou', 'porto-novo', 'abomey', 'parakou',
                          'ouidah', 'glo-djigbé', 'gdiz', 'kanvo', 'béninois', 'beninois'],
            'metier'  => ['textile', 'mode', 'couture', 'artisan', 'artisanat', 'styliste',
                          'pagne', 'tissu', 'indigo', 'batik', 'coton', 'confection', 'défilé'],
            'occasion' => ['appel à projet', 'appel a projet', 'concours', 'salon', 'foire',
                           'financement', 'subvention', 'formation', 'bourse', 'exportation'],
        ];
    }

    /**
     * CLI-1 — Catégories de la bibliothèque photos (P152).
     *
     * Le serveur acceptait déjà n'importe quelle valeur, mais la liste proposée
     * était figée dans l'écran : ajouter « Broderie » ou « Défilé » demandait un
     * déploiement. Elle vient désormais d'ici.
     *
     * `cle` est ce qui est stocké : la renommer orphelinerait les photos déjà
     * classées. Seul `label` se change librement — et il n'est utilisé que si
     * la traduction correspondante n'existe pas, pour que les catégories
     * d'origine restent traduites dans les deux langues.
     */
    public static function categoriesGalerie(): array
    {
        $cfg = static::where('cle', 'categories_galerie')->value('valeur');

        return is_array($cfg) && $cfg ? $cfg : [
            ['cle' => 'modele',      'label' => 'Modèle'],
            ['cle' => 'tissu',       'label' => 'Tissu'],
            ['cle' => 'occasion',    'label' => 'Occasion'],
            ['cle' => 'inspiration', 'label' => 'Inspiration'],
            ['cle' => 'client',      'label' => 'Client'],
        ];
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

    /**
     * Empreinte SHA-256 de chaque paquet OTA publié, par application et par
     * version — pour que l'appareil vérifie lui-même l'intégrité de ce qu'il
     * télécharge avant de l'installer.
     *
     * Jusqu'ici rien ne garantissait qu'un paquet servi était bien celui
     * publié : une écriture interrompue sur le serveur aurait été téléchargée
     * et installée sans qu'aucune vérification ne l'arrête. L'empreinte est
     * enregistrée séparément du dépôt du fichier (`release.sh` appelle
     * `app:enregistrer-checksum-ota` juste après), donc son absence ne bloque
     * jamais une publication : elle durcit une vérification qui n'existait
     * pas, elle n'en remplace aucune.
     *
     * Bornée à 20 versions par application : la seule utilisée est la plus
     * récente, l'historique ne sert qu'au diagnostic d'un incident encore
     * chaud.
     */
    public static function enregistrerChecksumOta(string $appId, string $version, string $sha256): void
    {
        $table = static::checksumsOta();
        $table[$appId] ??= [];
        $table[$appId][$version] = $sha256;
        $table[$appId] = array_slice($table[$appId], -20, null, true);

        static::updateOrCreate(['cle' => 'ota_checksums'], ['valeur' => $table]);
    }

    public static function checksumOta(string $appId, string $version): ?string
    {
        return static::checksumsOta()[$appId][$version] ?? null;
    }

    private static function checksumsOta(): array
    {
        $cfg = static::where('cle', 'ota_checksums')->value('valeur');

        return is_array($cfg) ? $cfg : [];
    }
}
