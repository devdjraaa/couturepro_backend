<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Proprietaire extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'telephone',
        'email',
        'nom',
        'prenom',
        'nom_atelier',
        'type_atelier',
        'question_secrete',
        'reponse_secrete',
        'password',
        'telephone_verified_at',
        'derniere_connexion_at',
        'naissance_jour',
        'naissance_mois',
        'fcm_token',
        'fcm_platform',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reponse_secrete',
    ];

    protected $casts = [
        'email_verified_at'     => 'datetime',
        'telephone_verified_at' => 'datetime',
        'password'              => 'hashed',
        'reponse_secrete'       => 'hashed',
    ];

    /**
     * Forme canonique d'un numéro : « + » initial conservé, uniquement des chiffres ensuite
     * (ex. « +229 90 00 00 88 » → « +22990000088 »). Évite les échecs de connexion dus à un
     * espace ou un format différent. Utilisée au stockage (mutateur) ET aux recherches.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }
        $phone   = trim($phone);
        $hasPlus = str_starts_with($phone, '+');
        $digits  = preg_replace('/\D/', '', $phone);

        return $digits === '' ? null : ($hasPlus ? '+' : '') . $digits;
    }

    // Normalise systématiquement le numéro à l'écriture.
    public function setTelephoneAttribute($value): void
    {
        $this->attributes['telephone'] = self::normalizePhone($value);
    }

    public function ateliers(): HasMany
    {
        return $this->hasMany(Atelier::class, 'proprietaire_id');
    }

    public function atelierMaitre()
    {
        return $this->hasOne(Atelier::class, 'proprietaire_id')->where('is_maitre', true);
    }

    public function equipesMembres(): HasMany
    {
        return $this->hasMany(EquipeMembre::class, 'created_by');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(TicketSupport::class, 'proprietaire_id');
    }
}
