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

    /** Modifiable par l'atelier tant qu'elle n'est pas soumise/publiée. */
    public function estEditable(): bool
    {
        return in_array($this->statut, [self::STATUT_BROUILLON, self::STATUT_REFUSEE], true);
    }
}
