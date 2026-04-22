<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OtpToken extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'otp_tokens';
    public $timestamps = false;

    protected $fillable = [
        'telephone',
        'code',
        'type',
        'expires_at',
        'used_at',
        'tentatives_echec',
        'created_at',
    ];

    protected $casts = [
        'expires_at'        => 'datetime',
        'used_at'           => 'datetime',
        'created_at'        => 'datetime',
        'tentatives_echec'  => 'integer',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return ! is_null($this->used_at);
    }

    public function isMaxAttempts(): bool
    {
        return $this->tentatives_echec >= 5;
    }
}
