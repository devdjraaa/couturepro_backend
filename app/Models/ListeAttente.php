<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// PL-4 : entrée de liste d'attente clients (Studio).
class ListeAttente extends Model
{
    use HasFactory;

    protected $table = 'liste_attente';

    protected $fillable = [
        'atelier_id', 'nom', 'telephone', 'note', 'statut', 'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
