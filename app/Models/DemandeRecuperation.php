<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandeRecuperation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'demandes_recuperation';

    protected $fillable = [
        'email',
        'telephone_nouveau',
        'statut',
        'token_opposition',
        'opposition_expire_at',
        'otp_envoye',
        'validated_at',
        'ip_address',
    ];

    protected $casts = [
        'opposition_expire_at' => 'datetime',
        'validated_at'         => 'datetime',
        'otp_envoye'           => 'boolean',
    ];
}
