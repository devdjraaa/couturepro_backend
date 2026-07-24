<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationCaisse extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'operations_caisse';

    protected $fillable = [
        'atelier_id', 'type', 'montant', 'motif', 'mode', 'created_by', 'created_by_role',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    /** Signe : +montant pour une entrée, -montant pour une sortie. */
    public function signe(): float
    {
        return $this->type === 'sortie' ? -(float) $this->montant : (float) $this->montant;
    }
}
