<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pt 24 — Notification destinée au CLIENT final (acheteur), pas à l'atelier.
 *
 * Distincte de `NotificationSysteme`, qui appartient aux ateliers : mélanger
 * deux populations dans une même table oblige chaque requête à se souvenir du
 * type de destinataire, et il suffit d'un oubli pour qu'un client lise les
 * notifications d'un atelier.
 */
class NotificationClient extends Model
{
    use HasUuids;

    protected $table = 'notifications_client';

    protected $fillable = [
        'gxt_client_id', 'type', 'titre', 'contenu', 'lien',
        'sujet_type', 'sujet_id', 'lu_at',
    ];

    protected $casts = ['lu_at' => 'datetime'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(GxtClient::class, 'gxt_client_id');
    }

    public function scopeNonLues($query)
    {
        return $query->whereNull('lu_at');
    }

    /** Les non lues d'abord, puis de la plus récente à la plus ancienne. */
    public function scopeOrdreAffichage($query)
    {
        return $query->orderByRaw('lu_at IS NULL DESC')->orderByDesc('created_at');
    }
}
