<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAuditLog extends Model
{
    use HasFactory;

    protected $table = 'admin_audit_log';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // append-only, created_at uniquement

    protected $fillable = [
        'admin_id',
        'action',
        'entite_type',
        'entite_id',
        'details',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'details'    => 'array',
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
