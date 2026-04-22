<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointsHistorique extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'points_historique';
    public $timestamps = false; // only created_at, immuable

    protected $fillable = [
        'atelier_id',
        'type',
        'points',
        'description',
        'reference_id',
        'created_at',
    ];

    protected $casts = [
        'points'     => 'integer',
        'created_at' => 'datetime',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
