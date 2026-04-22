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
        'question_secrete',
        'reponse_secrete',
        'password',
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
