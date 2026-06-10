<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandeItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'commande_id',
        'vetement_id',
        'vetement_nom',
        'quantite',
        'prix_unitaire',
        'description',
    ];

    protected $casts = [
        'quantite'     => 'integer',
        'prix_unitaire'=> 'decimal:2',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class);
    }
}
