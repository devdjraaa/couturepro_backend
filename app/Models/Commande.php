<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

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
        'photo_tissu_path',
        'urgence',
        'description',
        'rappel_j2_envoye',
    ];

    protected $appends = ['photo_tissu_url', 'client_nom', 'vetement_nom'];

    protected $casts = [
        'prix'                     => 'decimal:2',
        'acompte'                  => 'decimal:2',
        'date_commande'            => 'date',
        'date_livraison_prevue'    => 'date',
        'date_livraison_effective' => 'datetime',
        'rappel_j2_envoye'         => 'boolean',
        'urgence'                  => 'boolean',
    ];

    protected function photoTissuUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->photo_tissu_path ? url(Storage::url($this->photo_tissu_path)) : null,
        );
    }

    protected function clientNom(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(($this->client?->prenom ?? '') . ' ' . ($this->client?->nom ?? '')),
        );
    }

    protected function vetementNom(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->vetement?->nom ?? null,
        );
    }

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

    public function commandePaiements(): HasMany
    {
        return $this->hasMany(CommandePaiement::class, 'commande_id');
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
