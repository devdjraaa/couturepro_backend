<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

// P202 / Espace Client v3 — client final de la vitrine (auth sans mot de passe : Google ou OTP e-mail).
class GxtClient extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    protected $table = 'gxt_clients';

    protected $fillable = [
        'nom', 'prenom', 'email', 'telephone_whatsapp', 'google_id',
        'utm_source', 'utm_medium', 'utm_campaign', 'referrer_url',
        'appareil', 'systeme_os', 'navigateur', 'pays', 'ville', 'langue',
        'derniere_connexion_at',
    ];

    protected $casts = [
        'derniere_connexion_at' => 'datetime',
    ];

    public function consents(): HasMany
    {
        return $this->hasMany(GxtConsent::class, 'client_id');
    }

    /** Dernier consentement enregistré (source de vérité pour l'interrupteur tracking). */
    public function dernierConsentement(): HasOne
    {
        return $this->hasOne(GxtConsent::class, 'client_id')->latestOfMany();
    }
}
