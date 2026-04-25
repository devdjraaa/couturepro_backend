<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class EquipeMembre extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $table = 'equipe_membres';

    protected $fillable = [
        'atelier_id',
        'created_by',
        'code_acces',
        'nom',
        'prenom',
        'telephone',
        'role',
        'password',
        'device_id',
        'device_locked_at',
        'derniere_sync_at',
        'code_reprise',
        'code_reprise_expire_at',
        'is_active',
        'revoque_at',
    ];

    protected $hidden = ['password', 'remember_token', 'code_reprise'];

    protected $casts = [
        'password'               => 'hashed',
        'device_locked_at'       => 'datetime',
        'derniere_sync_at'       => 'datetime',
        'code_reprise_expire_at' => 'datetime',
        'revoque_at'             => 'datetime',
        'is_active'              => 'boolean',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(Proprietaire::class, 'created_by');
    }

    public function isDeviceLocked(): bool
    {
        return ! is_null($this->device_locked_at);
    }
}
