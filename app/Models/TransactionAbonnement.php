<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionAbonnement extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'transactions_abonnement';

    protected $fillable = [
        'code_transaction',
        'atelier_id',
        'paiement_id',
        'niveau_cle',
        'duree_jours',
        'montant',
        'devise',
        'canal',
        'statut',
        'utilise_at',
        'created_by',
    ];

    protected $casts = [
        'utilise_at' => 'datetime',
        'montant'    => 'decimal:2',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class, 'paiement_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(NiveauConfig::class, 'niveau_cle', 'cle');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'created_by');
    }

    public function scopeDisponible($query)
    {
        return $query->where('statut', 'disponible');
    }
}
