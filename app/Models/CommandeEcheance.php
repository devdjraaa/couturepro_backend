<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandeEcheance extends Model
{
    use HasUuids;

    protected $fillable = [
        'commande_id',
        'date_echeance',
        'note',
        'livree',
        'livree_at',
    ];

    protected $casts = [
        'date_echeance' => 'date',
        'livree'        => 'boolean',
        'livree_at'     => 'datetime',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class);
    }
}
