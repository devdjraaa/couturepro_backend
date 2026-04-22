<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'role',
        'permissions',
        'is_active',
        'derniere_connexion_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password'              => 'hashed',
        'permissions'           => 'array',
        'is_active'             => 'boolean',
        'derniere_connexion_at' => 'datetime',
    ];

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'super_admin') {
            return true;
        }
        if (! $this->permissions) {
            return false;
        }
        return in_array($permission, $this->permissions);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class, 'admin_id');
    }

    public function ticketsAssignes(): HasMany
    {
        return $this->hasMany(TicketSupport::class, 'assigned_to');
    }

    public function offresCreees(): HasMany
    {
        return $this->hasMany(OffreSpeciale::class, 'admin_id');
    }

    public function transactionsCreees(): HasMany
    {
        return $this->hasMany(TransactionAbonnement::class, 'created_by');
    }
}
