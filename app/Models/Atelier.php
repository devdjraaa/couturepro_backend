<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Atelier extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'proprietaire_id',
        'nom',
        'adresse',
        'ville',
        'is_maitre',
        'statut',
        'essai_expire_at',
        'is_demo',
        'type',
        'contact_public',
        'specialite',
        'bio',
        'logo_path',
        'instagram',
        'facebook',
        'site_web',
        'latitude',
        'longitude',
        'sponsor_jusqu_a',
        'verification_doc_path',
        'verification_lien',
        'verification_demandee_a',
    ];

    protected $casts = [
        'is_maitre'       => 'boolean',
        'is_demo'         => 'boolean',
        'contact_public'  => 'boolean',
        'verifie'         => 'boolean',
        'latitude'        => 'float',
        'longitude'       => 'float',
        'sponsor_jusqu_a' => 'datetime',
        'essai_expire_at' => 'datetime',
        'verification_demandee_a' => 'datetime',
    ];

    protected $appends = ['logo_url', 'sponsorise', 'verification_doc_url'];

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo_path ? url(Storage::url($this->logo_path)) : null,
        );
    }

    protected function sponsorise(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sponsor_jusqu_a !== null && $this->sponsor_jusqu_a->isFuture(),
        );
    }

    protected function verificationDocUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->verification_doc_path ? url(Storage::url($this->verification_doc_path)) : null,
        );
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class, 'atelier_id');
    }

    public function avis(): HasMany
    {
        return $this->hasMany(Avis::class, 'atelier_id');
    }

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(Proprietaire::class, 'proprietaire_id');
    }

    // Alias eager-loadable pour les notifications FCM
    public function getProprietaireWithFcmAttribute()
    {
        return $this->proprietaire()->select('id', 'fcm_token', 'fcm_platform')->first();
    }

    public function abonnement(): HasOne
    {
        return $this->hasOne(Abonnement::class, 'atelier_id')->latestOfMany('timestamp_debut');
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
