<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Paiement extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'atelier_id',
        'niveau_cle',
        'duree_jours',
        'montant',
        'devise',
        'provider',
        'provider_transaction_id',
        'provider_metadata',
        'statut',
        'checkout_url',
        'initiated_at',
        'webhook_received_at',
        'completed_at',
        'expires_at',
        'ip_address',
        'validated_by',
    ];

    protected $casts = [
        'provider_metadata'    => 'array',
        'initiated_at'         => 'datetime',
        'webhook_received_at'  => 'datetime',
        'completed_at'         => 'datetime',
        'expires_at'           => 'datetime',
        'montant'              => 'decimal:2',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(NiveauConfig::class, 'niveau_cle', 'cle');
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(TransactionAbonnement::class, 'paiement_id');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'validated_by');
    }

    public function scopePending($query)
    {
        return $query->where('statut', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('statut', 'pending')
                     ->where('expires_at', '<', now());
    }
}
