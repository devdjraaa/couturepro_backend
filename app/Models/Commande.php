<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Commande extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'atelier_id',
        'client_id',
        'vetement_id',
        'created_by',
        'created_by_role',
        'quantite',
        'prix',
        'acompte',
        'statut',
        'date_commande',
        'date_livraison_prevue',
        'date_livraison_effective',
        'note_interne',
        'rappel_j2_envoye',
    ];

    // ⚠️ photo_tissu_local_path N'EXISTE PAS ici — stockage LOCAL uniquement

    protected $casts = [
        'prix'                     => 'decimal:2',
        'acompte'                  => 'decimal:2',
        'date_commande'            => 'date',
        'date_livraison_prevue'    => 'date',
        'date_livraison_effective' => 'datetime',
        'rappel_j2_envoye'         => 'boolean',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function vetement(): BelongsTo
    {
        return $this->belongsTo(Vetement::class, 'vetement_id');
    }

    public function scopeEnCours($query)
    {
        return $query->where('statut', 'en_cours');
    }

    public function scopeRappelJ2($query)
    {
        $target = now()->addDays(2)->toDateString();
        return $query->where('statut', 'en_cours')
                     ->where('rappel_j2_envoye', false)
                     ->where('date_livraison_prevue', $target);
    }
}
