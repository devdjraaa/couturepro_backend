<?php

namespace App\Services;

use App\Models\GxtClient;
use App\Models\VitrineSetting;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Point 57 — Moteur d'événements dynamiques (célébrations).
 *
 * Célèbre des événements (nationaux, religieux, internes Gextimo, personnels,
 * marketing) SANS recompilation : le catalogue est config-driven (VitrineSetting,
 * clé `evenements_celebration`), éditable par l'admin, avec des valeurs par
 * défaut factuelles (jours fériés du Bénin).
 *
 * 5 familles :
 *   - fixe        : date fixe MM-JJ (fêtes nationales, Noël…)
 *   - lunaire     : dates explicites par année (fêtes religieuses mobiles)
 *   - gextimo     : événements internes (anniversaire de l'app…)
 *   - utilisateur : personnel, calculé (anniversaire du client connecté)
 *   - marketing   : fenêtre datée (promos), inclut le legacy `splash_themes`
 *
 * Priorité : personnel > Gextimo > national/religieux > marketing.
 * Fréquence : appliquée côté client (1×/jour ou 1× à vie selon frequence_affichage).
 * Cas limites : pas de date de naissance (aucun événement perso), conflits
 * multiples (tri par priorité), hors ligne (le front ignore), fuseau (Bénin).
 */
class EvenementCelebrationService
{
    /** Fuseau de référence (Bénin, WAT UTC+1, sans heure d'été). */
    public const FUSEAU = 'Africa/Porto-Novo';

    /** Bandes de priorité par famille (perso > gextimo > national/religieux > marketing). */
    private const BANDES = [
        'utilisateur' => 4000,
        'gextimo'     => 3000,
        'fixe'        => 2000,
        'lunaire'     => 2000,
        'marketing'   => 1000,
    ];

    /**
     * Événements applicables aujourd'hui, triés par priorité décroissante.
     *
     * @param  GxtClient|null  $client    client connecté (pour l'anniversaire perso)
     * @param  string          $contexte  'vitrine' (cibles tous/clients) ou 'app' (tous/pros)
     */
    public function duJour(?GxtClient $client = null, string $contexte = 'vitrine'): array
    {
        $now  = Carbon::now(self::FUSEAU);
        $mmjj = $now->format('m-d');
        $iso  = $now->format('Y-m-d');

        $ciblesOk = $contexte === 'app' ? ['tous', 'pros'] : ['tous', 'clients'];

        $evenements = [];

        foreach ($this->catalogue() as $e) {
            if (! ($e['actif'] ?? true)) {
                continue;
            }
            if (! in_array($e['cible'] ?? 'tous', $ciblesOk, true)) {
                continue;
            }

            // Famille « utilisateur » : anniversaire du client connecté (jour + mois).
            if (($e['type'] ?? 'fixe') === 'utilisateur') {
                if (! $client || ! $client->date_naissance) {
                    continue;
                }
                if (Carbon::parse($client->date_naissance)->format('m-d') !== $mmjj) {
                    continue;
                }
                $evenements[] = $this->normaliser($this->personnaliser($e, $client), $client);
                continue;
            }

            if (! $this->correspondAujourdhui($e, $mmjj, $iso)) {
                continue;
            }
            $evenements[] = $this->normaliser($e);
        }

        usort($evenements, fn ($a, $b) => $b['priorite_effective'] <=> $a['priorite_effective']);

        return $evenements;
    }

    /** Catalogue = config admin (VitrineSetting) fusionnée avec le legacy `splash_themes`. */
    public function catalogue(): array
    {
        $catalogue = VitrineSetting::evenementsCelebration();

        // Rétro-compatibilité : les anciens « splash themes » deviennent des
        // événements de la famille marketing (le splash saisonnier existant continue
        // de fonctionner sans reconfiguration).
        $legacy = VitrineSetting::where('cle', 'splash_themes')->value('valeur') ?: [];
        foreach ($legacy as $t) {
            $catalogue[] = [
                'code'                => 'splash_' . Str::slug($t['nom'] ?? uniqid()),
                'type'                => 'marketing',
                'date_debut'          => $t['date_debut'] ?? null,
                'date_fin'            => $t['date_fin'] ?? null,
                'titre'               => ['fr' => $t['nom'] ?? '', 'en' => $t['nom'] ?? ''],
                'message'             => ['fr' => $t['texte'] ?? '', 'en' => $t['texte'] ?? ''],
                'image_url'           => $t['image_url'] ?? null,
                'animation'           => 'aucune',
                'couleur'             => '#C4162A',
                'priorite'            => 0,
                'cible'               => 'tous',
                'mode_affichage'      => 'splash',
                'frequence_affichage' => 'quotidien',
                'actif'               => $t['actif'] ?? false,
            ];
        }

        return $catalogue;
    }

    /** L'événement se déclenche-t-il aujourd'hui ? (selon sa famille) */
    private function correspondAujourdhui(array $e, string $mmjj, string $iso): bool
    {
        return match ($e['type'] ?? 'fixe') {
            'fixe', 'gextimo' => ! empty($e['date_fixe']) && $e['date_fixe'] === $mmjj,
            'lunaire'         => in_array($iso, $e['dates'] ?? [], true),
            'marketing'       => ($e['date_debut'] ?? '9999') <= $iso && ($e['date_fin'] ?? '0000') >= $iso,
            default           => false,
        };
    }

    /** Substitue {prenom} dans le gabarit d'anniversaire. */
    private function personnaliser(array $e, GxtClient $client): array
    {
        $prenom = trim($client->prenom ?: $client->nom ?: '');
        foreach (['titre', 'message'] as $champ) {
            foreach (['fr', 'en'] as $lang) {
                $val = $e[$champ][$lang] ?? '';
                // Sans prénom : on retire proprement le gabarit et l'espace superflu.
                $e[$champ][$lang] = $prenom
                    ? str_replace('{prenom}', $prenom, $val)
                    : trim(preg_replace('/\s+/', ' ', str_replace('{prenom}', '', $val)));
            }
        }

        return $e;
    }

    /** Forme de sortie stable pour le front (+ priorité effective calculée). */
    private function normaliser(array $e, ?GxtClient $client = null): array
    {
        $type = $e['type'] ?? 'fixe';
        $code = $e['code'] ?? uniqid('evt_');

        // Anniversaire : identifiant par client + année, pour un affichage 1×/an.
        if ($type === 'utilisateur' && $client) {
            $code .= '_' . $client->id . '_' . Carbon::now(self::FUSEAU)->year;
        }

        return [
            'code'                => $code,
            'type'                => $type,
            'titre'               => $e['titre'] ?? ['fr' => '', 'en' => ''],
            'message'             => $e['message'] ?? ['fr' => '', 'en' => ''],
            'animation'           => $e['animation'] ?? 'aucune',
            'couleur'             => $e['couleur'] ?? '#C4162A',
            'image_url'           => $e['image_url'] ?? null,
            'mode_affichage'      => $e['mode_affichage'] ?? 'splash',
            'frequence_affichage' => $e['frequence_affichage'] ?? 'quotidien',
            'priorite_effective'  => (self::BANDES[$type] ?? 0) + (int) ($e['priorite'] ?? 0),
        ];
    }
}
