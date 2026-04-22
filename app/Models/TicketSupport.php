<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TicketSupport extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'tickets_support';

    protected $fillable = [
        'reference',
        'atelier_id',
        'proprietaire_id',
        'categorie',
        'priorite',
        'statut',
        'sujet',
        'assigned_to',
        'resolu_at',
    ];

    protected $casts = [
        'resolu_at' => 'datetime',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(Proprietaire::class, 'proprietaire_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id')->orderBy('created_at');
    }

    public static function genererReference(): string
    {
        return 'TKT-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4));
    }
}
