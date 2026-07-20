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
