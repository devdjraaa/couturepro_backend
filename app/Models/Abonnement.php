<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Abonnement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'atelier_id',
        'niveau_cle',
        'statut',
        'jours_restants',
        'timestamp_debut',
        'timestamp_expiration',
        'bonus_actif',
        'bonus_jours_restants',
        'bonus_niveau_cle',
        'bonus_timestamp_debut',
        'config_snapshot',
    ];

    protected $casts = [
        'config_snapshot'       => 'array',
        'timestamp_debut'       => 'datetime',
        'timestamp_expiration'  => 'datetime',
        'bonus_timestamp_debut' => 'datetime',
        'bonus_actif'           => 'boolean',
        'jours_restants'        => 'integer',
        'bonus_jours_restants'  => 'integer',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(NiveauConfig::class, 'niveau_cle', 'cle');
    }

    public function bonusNiveau(): BelongsTo
    {
        return $this->belongsTo(NiveauConfig::class, 'bonus_niveau_cle', 'cle');
    }

    // Helper : retourne la config effective (snapshot ou plan direct)
    public function getConfigEffective(): array
    {
        return $this->config_snapshot ?? $this->niveau?->config ?? [];
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }
}
