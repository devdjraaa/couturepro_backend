<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// P153-158 : code promo (événement à expiration) ou ambassadeur (permanent, suivi).
class CodePromo extends Model
{
    use HasUuids;

    protected $table = 'codes_promo';

    protected $fillable = [
        'code', 'type', 'jours_bonus', 'expire_at',
        'max_utilisations', 'is_actif', 'note', 'created_by',
    ];

    protected $casts = [
        'expire_at'        => 'datetime',
        'is_actif'         => 'boolean',
        'jours_bonus'      => 'integer',
        'max_utilisations' => 'integer',
    ];

    public function utilisations(): HasMany
    {
        return $this->hasMany(CodePromoUtilisation::class);
    }

    // Le code est-il utilisable en ce moment (hors contrainte « déjà utilisé par ce téléphone ») ?
    public function estValide(): bool
    {
        if (! $this->is_actif) {
            return false;
        }
        if ($this->expire_at && $this->expire_at->isPast()) {
            return false;
        }
        if ($this->max_utilisations !== null
            && $this->utilisations()->count() >= $this->max_utilisations) {
            return false;
        }

        return true;
    }
}
