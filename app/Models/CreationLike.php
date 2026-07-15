<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// P159-160 : like public anonyme sur une création (vêtement publié en vitrine).
class CreationLike extends Model
{
    use HasUuids;

    public const UPDATED_AT = null; // append-only, pas de mise à jour

    protected $fillable = ['vetement_id', 'visitor_key'];

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class, 'vetement_id');
    }
}
