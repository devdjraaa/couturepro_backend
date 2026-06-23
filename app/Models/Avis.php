<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Avis extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'avis';

    protected $fillable = ['atelier_id', 'auteur_nom', 'note', 'texte', 'statut'];

    protected $casts = ['note' => 'integer'];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
