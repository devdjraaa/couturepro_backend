<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'tickets_messages';
    public $timestamps = false;

    protected $fillable = [
        'ticket_id',
        'expediteur_type',
        'expediteur_id',
        'contenu',
        'pj_path',
        'is_note_interne',
        'lu_par_client_at',
        'lu_par_admin_at',
        'created_at',
    ];

    protected $casts = [
        'is_note_interne'  => 'boolean',
        'lu_par_client_at' => 'datetime',
        'lu_par_admin_at'  => 'datetime',
        'created_at'       => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(TicketSupport::class, 'ticket_id');
    }
}
