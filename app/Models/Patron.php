<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// P161-163 : contenu numérique payant (patron) attaché à une création.
class Patron extends Model
{
    use HasUuids;

    protected $fillable = [
        'atelier_id', 'vetement_id', 'titre', 'description',
        'prix', 'fichier_path', 'fichier_nom', 'fichier_taille', 'actif',
    ];

    protected $casts = [
        'prix'          => 'integer',
        'fichier_taille' => 'integer',
        'actif'         => 'boolean',
    ];

    // Le chemin du fichier ne doit JAMAIS être exposé publiquement.
    protected $hidden = ['fichier_path'];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class, 'vetement_id');
    }

    public function achats(): HasMany
    {
        return $this->hasMany(PatronAchat::class, 'patron_id');
    }
}
