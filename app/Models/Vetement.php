<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vetement extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'atelier_id',
        'nom',
        'libelles_mesures',
        'template_numero',
        'is_systeme',
        'is_archived',
        'created_by',
        'created_by_role',
    ];

    protected $casts = [
        'libelles_mesures' => 'array',
        'is_systeme'       => 'boolean',
        'is_archived'      => 'boolean',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function mesures(): HasMany
    {
        return $this->hasMany(Mesure::class, 'vetement_id');
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class, 'vetement_id');
    }

    public function scopeSysteme($query)
    {
        return $query->where('is_systeme', true);
    }

    public function scopeActif($query)
    {
        return $query->where('is_archived', false);
    }
}
