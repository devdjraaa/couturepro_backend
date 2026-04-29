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
    // Le plan live remplit les clés absentes du snapshot (ajoutées après activation)
    public function getConfigEffective(): array
    {
        $snapshot = $this->config_snapshot;

        if (is_string($snapshot)) {
            $snapshot = json_decode($snapshot, true) ?? [];
        }

        $liveConfig = $this->niveau?->config;
        $live = is_array($liveConfig) ? $liveConfig : (json_decode($liveConfig, true) ?? []);

        if (is_array($snapshot) && !empty($snapshot)) {
            // Snapshot prend priorité ; live remplit les clés manquantes
            return array_merge($live, $snapshot);
        }

        return $live;
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }
}
