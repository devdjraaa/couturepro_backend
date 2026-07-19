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
        'atelier_id', 'collection_id', 'auteur_nom', 'note', 'texte', 'photos', 'statut',
        'gxt_client_id', 'commande_id', 'signalements_count', 'signale_at',
    ];

    protected $casts = [
        'note'               => 'integer',
        'photos'             => 'array',
        'signalements_count' => 'integer',
        'signale_at'         => 'datetime',
    ];

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

    /** S08C-29e — collection visée par l'avis (facultatif : sinon l'avis vise le créateur). */
    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class, 'collection_id');
    }

    /** Avis visibles publiquement. */
    public function scopeValides($q)
    {
        return $q->where('statut', 'valide');
    }
}
