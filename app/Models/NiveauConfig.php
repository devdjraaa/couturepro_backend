<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NiveauConfig extends Model
{
    use HasFactory;

    protected $table = 'niveaux_config';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'cle',
        'label',
        'duree_jours',
        'prix_xof',
        'prix_mensuel_equivalent_xof',
        'config',
        'is_actif',
        'ordre_affichage',
        'description_courte',
        'updated_by',
    ];

    protected $casts = [
        'config'                        => 'array',
        'prix_xof'                      => 'decimal:2',
        'prix_mensuel_equivalent_xof'   => 'decimal:2',
        'is_actif'                      => 'boolean',
        'duree_jours'                   => 'integer',
        'ordre_affichage'               => 'integer',
    ];

    // Relations
    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class, 'niveau_cle', 'cle');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'niveau_cle', 'cle');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TransactionAbonnement::class, 'niveau_cle', 'cle');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('is_actif', true)->orderBy('ordre_affichage');
    }
}
