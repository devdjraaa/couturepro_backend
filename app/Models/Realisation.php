<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Point 101 — « Mes Réalisations » : une réalisation (photos d'un ouvrage fini)
 * publiée par un atelier, soumise à modération avant d'apparaître publiquement.
 */
class Realisation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'realisations';

    protected $fillable = [
        'atelier_id', 'titre', 'description', 'images', 'statut',
        'certifie_auteur', 'consentement_personnes', 'motif_refus',
        'modere_par', 'modere_at', 'soumis_at', 'publie_at',
    ];

    protected $casts = [
        'images'                 => 'array',
        'certifie_auteur'        => 'boolean',
        'consentement_personnes' => 'boolean',
        'modere_at'              => 'datetime',
        'soumis_at'              => 'datetime',
        'publie_at'              => 'datetime',
    ];

    // Les 4 statuts (chacun avec un badge visible côté UI).
    public const STATUT_BROUILLON  = 'brouillon';
    public const STATUT_EN_ATTENTE  = 'en_attente';
    public const STATUT_PUBLIEE     = 'publiee';
    public const STATUT_REFUSEE     = 'refusee';

    public const STATUTS = [
        self::STATUT_BROUILLON,
        self::STATUT_EN_ATTENTE,
        self::STATUT_PUBLIEE,
        self::STATUT_REFUSEE,
    ];

    /** Anti-abus : nombre maximum d'envois en modération par semaine et par atelier. */
    public const MAX_ENVOIS_SEMAINE = 10;

    /** PHOTO-5 — Cycle de publication : reset le 22 de chaque mois à 00h00, heure de Cotonou. */
    public const FUSEAU     = 'Africa/Porto-Novo';
    public const JOUR_RESET = 22;

    /** Début du cycle en cours (le 22 le plus récent). */
    public static function debutCycle(): \Carbon\CarbonImmutable
    {
        $maintenant = \Carbon\CarbonImmutable::now(self::FUSEAU);
        $resetDuMois = $maintenant->startOfMonth()->addDays(self::JOUR_RESET - 1)->startOfDay();

        return $maintenant->gte($resetDuMois) ? $resetDuMois : $resetDuMois->subMonth();
    }

    /** Date du prochain renouvellement (affichée en permanence au designer). */
    public static function prochainReset(): \Carbon\CarbonImmutable
    {
        return self::debutCycle()->addMonth();
    }

    /** Cap du cache local (brouillons + en attente uniquement), aligné avec la spec. */
    public const CAP_CACHE_LOCAL = 100;

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    public function scopePubliees($q)
    {
        return $q->where('statut', self::STATUT_PUBLIEE);
    }

    public function scopeEnAttente($q)
    {
        return $q->where('statut', self::STATUT_EN_ATTENTE);
    }

    /**
     * PHOTO-4 — Réalisations consommant le quota du cycle en cours.
     *
     * Le solde n'est PAS stocké : il se déduit des faits, ce qui rend toute dérive
     * impossible. La règle de la direction tombe alors d'elle-même :
     *   • décrément À L'ENVOI  → on ne compte que les `soumis_at` renseignés
     *     (un brouillon n'a rien consommé) ;
     *   • +1 si REFUS définitif → les refusées sont exclues du décompte ;
     *   • +1 si SUPPRESSION avant publication → la ligne n'existe plus ;
     *   • jamais de réattribution après publication → les publiées restent comptées.
     */
    public function scopeConsommeesCycle($q)
    {
        return $q->whereNotNull('soumis_at')
            ->where('soumis_at', '>=', self::debutCycle()->utc())
            ->whereIn('statut', [self::STATUT_EN_ATTENTE, self::STATUT_PUBLIEE]);
    }

    /** Modifiable par l'atelier tant qu'elle n'est pas soumise/publiée. */
    public function estEditable(): bool
    {
        return in_array($this->statut, [self::STATUT_BROUILLON, self::STATUT_REFUSEE], true);
    }
}
