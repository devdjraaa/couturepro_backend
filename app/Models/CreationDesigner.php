<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreationDesigner extends Model
{
    use HasFactory;

    protected $table = 'creations_designer';

    protected $fillable = [
        'atelier_id', 'categorie', 'titre', 'description',
        'images', 'metadata', 'public',
    ];

    protected $casts = [
        'images'   => 'array',
        'metadata' => 'array',
        'public'   => 'boolean',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }
}
