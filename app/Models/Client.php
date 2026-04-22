<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'atelier_id',
        'nom',
        'prenom',
        'telephone',
        'type_profil',
        'avatar_key',
        'created_by',
        'created_by_role',
        'is_archived',
        'archived_at',
        'archived_by',
    ];

    // ⚠️ photo_local_path N'EXISTE PAS ici — stockage LOCAL uniquement

    protected $casts = [
        'is_archived' => 'boolean',
        'archived_at' => 'datetime',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function mesures(): HasMany
    {
        return $this->hasMany(Mesure::class, 'client_id');
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class, 'client_id');
    }

    public function scopeActif($query)
    {
        return $query->where('is_archived', false);
    }
}
