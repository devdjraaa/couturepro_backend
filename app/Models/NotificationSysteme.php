<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSysteme extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'notifications_systeme';

    protected $fillable = [
        'atelier_id',
        'titre',
        'contenu',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function scopeNonLues($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeBroadcast($query)
    {
        return $query->whereNull('atelier_id');
    }
}
