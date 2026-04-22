<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsFidelite extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'points_fidelite';

    protected $fillable = [
        'atelier_id',
        'solde_pts',
    ];

    protected $casts = [
        'solde_pts' => 'integer',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
