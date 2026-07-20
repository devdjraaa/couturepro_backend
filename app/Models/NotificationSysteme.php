<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSysteme extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notifications_systeme';

    protected $fillable = [
        'atelier_id',
        'titre',
        'contenu',
        'type',
        'lien',
        'is_read',
        // CLI-2 — « Gextimo Infos »
        'canal',
        'categorie',
        'cible',
        'epingle',
        'publie_at',
        'expire_at',
    ];

    protected $casts = [
        'is_read'   => 'boolean',
        'epingle'   => 'boolean',
        'cible'     => 'array',
        'publie_at' => 'datetime',
        'expire_at' => 'datetime',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function scopeNonLues($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeBroadcast($query)
    {
        return $query->whereNull('atelier_id');
    }

    /** CLI-2 — l'onglet « Gextimo Infos », distinct des notifications d'atelier. */
    public function scopeInfos($query)
    {
        return $query->where('canal', 'info');
    }

    /**
     * Infos réellement diffusables à l'instant présent.
     *
     * `publie_at` nul vaut « publiée » : une info créée sans programmation part
     * tout de suite, ce qui est le cas courant. Ne pas traiter ce cas rendrait
     * invisible toute info dont on a oublié de renseigner la date.
     */
    public function scopePubliees($query)
    {
        return $query
            ->where(fn ($q) => $q->whereNull('publie_at')->orWhere('publie_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expire_at')->orWhere('expire_at', '>', now()));
    }
}
