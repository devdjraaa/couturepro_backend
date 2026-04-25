<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Atelier extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'proprietaire_id',
        'nom',
        'is_maitre',
        'statut',
        'essai_expire_at',
        'is_demo',
    ];

    protected $casts = [
        'is_maitre'       => 'boolean',
        'is_demo'         => 'boolean',
        'essai_expire_at' => 'datetime',
    ];

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(Proprietaire::class, 'proprietaire_id');
    }

    public function abonnement(): HasOne
    {
        return $this->hasOne(Abonnement::class, 'atelier_id');
    }

    public function equipesMembres(): HasMany
    {
        return $this->hasMany(EquipeMembre::class, 'atelier_id');
    }

    public function parametres(): HasOne
    {
        return $this->hasOne(ParametresAtelier::class, 'atelier_id');
    }

    public function communicationsConfig(): HasOne
    {
        return $this->hasOne(CommunicationsConfig::class, 'atelier_id');
    }

    public function pointsFidelite(): HasOne
    {
        return $this->hasOne(PointsFidelite::class, 'atelier_id');
    }

    public function pointsHistorique(): HasMany
    {
        return $this->hasMany(PointsHistorique::class, 'atelier_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationSysteme::class, 'atelier_id');
    }

    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class, 'atelier_id');
    }

    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'atelier_id');
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class, 'atelier_id');
    }

    public function quotas(): HasMany
    {
        return $this->hasMany(QuotaMensuel::class, 'atelier_id');
    }

    public function photosVip(): HasMany
    {
        return $this->hasMany(PhotoVip::class, 'atelier_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'atelier_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TransactionAbonnement::class, 'atelier_id');
    }

    public function offresSpeciales(): HasMany
    {
        return $this->hasMany(OffreSpeciale::class, 'atelier_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(TicketSupport::class, 'atelier_id');
    }

    public function quotaMoisCourant(): HasOne
    {
        return $this->hasOne(QuotaMensuel::class, 'atelier_id')
            ->where('annee', now()->year)
            ->where('mois', now()->month);
    }
}
