<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PL-7 / VID-3,4,5 — Vidéo de présentation d'un atelier.
 *
 * Deux sources possibles : lien YouTube ou fichier importé (tous les créateurs
 * n'ont pas de chaîne). Aucune vidéo n'est publiée sans validation : soumission
 * → « en attente » → publiée ou refusée sous 24 h.
 */
class AtelierVideo extends Model
{
    use HasFactory;

    protected $table = 'atelier_videos';

    protected $fillable = [
        'atelier_id', 'titre', 'url', 'position', 'statut', 'source',
        'fichier_path', 'motif_refus', 'soumis_at', 'modere_at', 'modere_par',
    ];

    protected $casts = [
        'position'  => 'integer',
        'soumis_at' => 'datetime',
        'modere_at' => 'datetime',
    ];

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_PUBLIEE    = 'publiee';
    public const STATUT_REFUSEE    = 'refusee';

    public const SOURCE_YOUTUBE = 'youtube';
    public const SOURCE_FICHIER = 'fichier';

    /** Délai maximum de validation annoncé au créateur. */
    public const DELAI_VALIDATION_HEURES = 24;

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    /**
     * Vidéos consommant le quota du plan.
     *
     * Les REFUSÉES en sont exclues : le quota est ainsi restitué automatiquement,
     * sans compteur à maintenir ni risque de dérive.
     */
    public function scopeConsommentQuota($q)
    {
        return $q->whereIn('statut', [self::STATUT_EN_ATTENTE, self::STATUT_PUBLIEE]);
    }

    /** Seules les vidéos validées sont visibles publiquement. */
    public function scopePubliees($q)
    {
        return $q->where('statut', self::STATUT_PUBLIEE);
    }

    /** Échéance de modération (24 h après la soumission). */
    public function limiteModeration(): ?\Illuminate\Support\Carbon
    {
        return $this->soumis_at?->copy()->addHours(self::DELAI_VALIDATION_HEURES);
    }
}
