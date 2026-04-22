<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametresAtelier extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'parametres_atelier';

    protected $fillable = [
        'atelier_id',
        'langue',
        'devise',
        'unite_mesure',
        'theme',
        'mode_sync_photos',
        'multi_ateliers_actif',
    ];

    protected $casts = [
        'multi_ateliers_actif' => 'boolean',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
