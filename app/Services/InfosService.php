<?php

namespace App\Services;

use App\Models\Atelier;
use App\Models\NotificationSysteme;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CLI-2 — « Gextimo Infos » : qui reçoit quoi.
 *
 * La résolution du ciblage vit ICI, côté serveur, et jamais dans l'écran. Une
 * info « réservée aux comptes Designer » filtrée côté client serait quand même
 * transmise à tout le monde : il suffirait d'ouvrir les outils du navigateur
 * pour lire une annonce qui ne s'adresse pas à soi. Le serveur n'envoie que ce
 * que l'atelier a le droit de voir.
 */
class InfosService
{
    /** Modes de ciblage reconnus. Un mode inconnu ne diffuse à personne. */
    public const MODES = ['tous', 'types_compte', 'plans', 'villes', 'ateliers'];

    /**
     * Infos visibles par un atelier, épinglées d'abord puis les plus récentes.
     *
     * `avec_lu` ajoute l'état de lecture PAR ATELIER : sur une diffusion
     * générale, `is_read` porté par la ligne serait partagé par tous les
     * destinataires — le premier lecteur masquerait l'annonce pour tout le monde.
     */
    public function pourAtelier(Atelier $atelier, int $limite = 50): Collection
    {
        $infos = NotificationSysteme::query()
            ->where('canal', 'info')
            ->publiees()
            ->orderByDesc('epingle')
            ->orderByDesc('publie_at')
            ->limit($limite * 3)   // marge : le ciblage retire ensuite
            ->get()
            ->filter(fn (NotificationSysteme $i) => $this->concerne($i, $atelier))
            ->take($limite)
            ->values();

        if ($infos->isEmpty()) {
            return new Collection();
        }

        $lues = DB::table('infos_lectures')
            ->where('atelier_id', $atelier->id)
            ->whereIn('notification_id', $infos->pluck('id'))
            ->pluck('lu_at', 'notification_id');

        return $infos->each(function (NotificationSysteme $i) use ($lues) {
            $i->setAttribute('lu', isset($lues[$i->id]));
            $i->setAttribute('lu_at', $lues[$i->id] ?? null);
        });
    }

    /** Nombre d'infos non lues — sert la pastille de l'onglet. */
    public function nonLues(Atelier $atelier): int
    {
        return $this->pourAtelier($atelier)->where('lu', false)->count();
    }

    /**
     * L'info s'adresse-t-elle à cet atelier ?
     *
     * Absence de ciblage = tout le monde : c'est le comportement historique de
     * la diffusion générale, qu'on ne change pas sous les pieds des messages
     * déjà en base.
     */
    public function concerne(NotificationSysteme $info, Atelier $atelier): bool
    {
        $cible = $info->cible;

        if (! is_array($cible) || empty($cible['mode']) || $cible['mode'] === 'tous') {
            return true;
        }

        $valeurs = array_map('strval', (array) ($cible['valeurs'] ?? []));

        // Une cible sans valeur ne désigne personne. Diffuser à tous serait le
        // pire des deux : une annonce réservée partirait à toute la base par
        // simple oubli de saisie.
        if ($valeurs === []) {
            return false;
        }

        return match ($cible['mode']) {
            'types_compte' => in_array((string) $atelier->type, $valeurs, true),
            'villes'       => in_array(mb_strtolower((string) $atelier->ville), array_map('mb_strtolower', $valeurs), true),
            'ateliers'     => in_array((string) $atelier->id, $valeurs, true),
            'plans'        => in_array((string) ($atelier->abonnement?->niveau_cle), $valeurs, true),
            default        => false,   // mode inconnu : on ne diffuse pas
        };
    }

    /** Marque une info lue par cet atelier, sans jamais doublonner. */
    public function marquerLue(NotificationSysteme $info, Atelier $atelier): void
    {
        DB::table('infos_lectures')->updateOrInsert(
            ['notification_id' => $info->id, 'atelier_id' => $atelier->id],
            ['id' => (string) \Illuminate\Support\Str::uuid(), 'lu_at' => now(), 'updated_at' => now(), 'created_at' => now()],
        );
    }

    /**
     * Nombre d'ateliers effectivement touchés par un ciblage.
     * Sert l'aperçu de l'écran de diffusion : la direction doit voir combien de
     * personnes recevront le message AVANT de l'envoyer.
     */
    public function portee(array $cible): int
    {
        $mode = $cible['mode'] ?? 'tous';
        $valeurs = (array) ($cible['valeurs'] ?? []);

        $q = Atelier::query();

        return match ($mode) {
            'tous'         => $q->count(),
            'types_compte' => $q->whereIn('type', $valeurs)->count(),
            'villes'       => $q->whereIn(DB::raw('lower(ville)'), array_map('mb_strtolower', $valeurs))->count(),
            'ateliers'     => $q->whereIn('id', $valeurs)->count(),
            'plans'        => $q->whereHas('abonnement', fn ($a) => $a->whereIn('niveau_cle', $valeurs))->count(),
            default        => 0,
        };
    }
}
