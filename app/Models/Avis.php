<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Avis extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'avis';

    protected $fillable = [
        'atelier_id', 'vetement_id', 'collection_id', 'auteur_nom', 'note', 'texte',
        'photos', 'photos_statut', 'statut',
        'gxt_client_id', 'commande_id', 'achat_verifie',
        'signalements_count', 'signale_at', 'revue_prioritaire',
    ];

    protected $casts = [
        'note'               => 'integer',
        'photos'             => 'array',
        'achat_verifie'      => 'boolean',
        'revue_prioritaire'  => 'boolean',
        'signalements_count' => 'integer',
        'signale_at'         => 'datetime',
    ];

    public const PHOTOS_EN_ATTENTE = 'en_attente';
    public const PHOTOS_VALIDEES   = 'validees';
    public const PHOTOS_REFUSEES   = 'refusees';

    protected $appends = ['photos_urls'];

    // P137 : URLs publiques des photos jointes (le chemin brut reste interne).
    protected function photosUrls(): Attribute
    {
        return Attribute::make(
            get: fn () => collect($this->photos ?? [])->map(fn ($p) => url(Storage::url($p)))->all(),
        );
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    /** Historique (piste abandonnée le 20/07) : quelques lignes portent encore une collection. */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    /** Décision 1 (20/07) — l'avis vise un MODÈLE. Nul = avis historique niveau créateur. */
    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class, 'vetement_id');
    }

    public function clientAuteur(): BelongsTo
    {
        return $this->belongsTo(GxtClient::class, 'gxt_client_id');
    }

    /**
     * Photos montrables au PUBLIC : uniquement celles validées par l'admin
     * (décision 11 — une photo indécente visible même quelques minutes cause un
     * tort réel). L'accesseur `photos_urls` reste complet pour l'admin.
     */
    public function photosPubliques(): array
    {
        return $this->photos_statut === self::PHOTOS_VALIDEES ? $this->photos_urls : [];
    }

    /** Avis visibles publiquement. */
    public function scopeValides($q)
    {
        return $q->where('statut', 'valide');
    }
}
