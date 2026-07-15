<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// P74 : une version figée d'une série de mesures (append-only, pas de updated_at).
class MesureVersion extends Model
{
    use HasUuids;

    public const UPDATED_AT = null; // append-only

    protected $fillable = [
        'mesure_id',
        'client_id',
        'atelier_id',
        'version',
        'champs',
        'created_by',
        'created_by_role',
        'created_at',
    ];

    protected $casts = [
        'champs'     => 'array',
        'version'    => 'integer',
        'created_at' => 'datetime',
    ];

    public function mesure(): BelongsTo
    {
        return $this->belongsTo(Mesure::class, 'mesure_id');
    }

    public function auteur(): BelongsTo
    {
        return $this->belongsTo(Proprietaire::class, 'created_by');
    }
}
