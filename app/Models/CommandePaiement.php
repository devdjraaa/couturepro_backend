<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommandePaiement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'commande_id',
        'atelier_id',
        'montant',
        'mode_paiement',
        'enregistre_par',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }
}
