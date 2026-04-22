<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class OffreSpeciale extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'offres_speciales';

    protected $fillable = [
        'atelier_id',
        'admin_id',
        'label',
        'niveau_base_cle',
        'config_override',
        'prix_special',
        'duree_jours',
        'statut',
        'expire_at',
        'notes_internes',
    ];

    protected $casts = [
        'config_override' => 'array',
        'prix_special'    => 'decimal:2',
        'expire_at'       => 'datetime',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function niveauBase(): BelongsTo
    {
        return $this->belongsTo(NiveauConfig::class, 'niveau_base_cle', 'cle');
    }

    // Retourne la config fusionnée : plan de base + override
    public function getConfigFusionnee(): array
    {
        $base     = $this->niveauBase?->config ?? [];
        $override = $this->config_override ?? [];
        return array_merge($base, $override);
    }

    public function scopeActif($query)
    {
        return $query->where('statut', 'actif');
    }
}
