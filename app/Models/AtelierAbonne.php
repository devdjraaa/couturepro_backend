<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ABO-1 — Abonnement à un créateur, rattaché à un COMPTE client.
 *
 * Historiquement anonyme (clé visiteur en stockage navigateur) : ces lignes
 * héritées sont conservées, `visitor_key` reste donc nullable. Les nouveaux
 * abonnements exigent un compte, avec un consentement notifications distinct.
 */
class AtelierAbonne extends Model
{
    use HasUuids;

    protected $table = 'atelier_abonnes';

    protected $fillable = [
        'atelier_id', 'gxt_client_id', 'visitor_key',
        'notifications_optin', 'actif', 'desabonne_at',
    ];

    protected $casts = [
        'notifications_optin' => 'boolean',
        'actif'               => 'boolean',
        'desabonne_at'        => 'datetime',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(GxtClient::class, 'gxt_client_id');
    }

    /** Abonnements réellement actifs (un désabonnement conserve la ligne). */
    public function scopeActifs(Builder $q): Builder
    {
        return $q->where('actif', true);
    }
}
