<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mesure extends Model
{
    use HasFactory, HasUuids;

    // P74 : à chaque fois que les mesures (champs) changent, on fige une version dans
    // l'historique (date, atelier, auteur, n° de version). Couvre toutes les voies d'écriture.
    protected static function booted(): void
    {
        static::saved(function (Mesure $mesure) {
            $derniere = $mesure->versions()->orderByDesc('version')->first();

            // On ne fige une version que si les mesures diffèrent réellement de la dernière.
            // NB : on compare les tableaux `champs` (== ignore l'ordre des clés) plutôt que
            // wasChanged('champs') — peu fiable sur colonne JSON (faux positifs à chaque save).
            if ($derniere && $derniere->champs == $mesure->champs) {
                return;
            }

            $mesure->versions()->create([
                'client_id'       => $mesure->client_id,
                'atelier_id'      => $mesure->atelier_id,
                'version'         => ($derniere->version ?? 0) + 1,
                'champs'          => $mesure->champs,
                'created_by'      => $mesure->created_by,
                'created_by_role' => $mesure->created_by_role,
                'created_at'      => now(),
            ]);
        });
    }

    protected $fillable = [
        'client_id',
        'atelier_id',
        'champs',
        'created_by',
        'created_by_role',
        'is_archived',
        'archived_at',
        'archived_by',
        'archive_note',
    ];

    protected $casts = [
        'champs'       => 'array',
        'is_archived'  => 'boolean',
        'archived_at'  => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(MesureVersion::class, 'mesure_id');
    }
}
