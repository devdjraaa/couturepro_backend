<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mesure extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'client_id',
        'atelier_id',
        'champs',
        'created_by',
        'created_by_role',
        'is_archived',
        'archived_at',
        'archived_by',
        'archive_note',
    ];

    protected $casts = [
        'champs'       => 'array',
        'is_archived'  => 'boolean',
        'archived_at'  => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
