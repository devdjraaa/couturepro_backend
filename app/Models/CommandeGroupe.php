<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommandeGroupe extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'atelier_id',
        'client_id',
        'created_by',
        'created_by_role',
        'note',
    ];

    protected $appends = ['client_nom', 'total_general', 'acompte_total', 'reste_total'];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class, 'commande_groupe_id');
    }

    protected function clientNom(): Attribute
    {
        return Attribute::make(
            get: fn () => trim(($this->client?->prenom ?? '') . ' ' . ($this->client?->nom ?? '')),
        );
    }

    protected function totalGeneral(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->commandes->sum(fn ($c) => (float) $c->prix),
        );
    }

    protected function acompteTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->commandes->sum(fn ($c) => (float) $c->acompte),
        );
    }

    protected function resteTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => max(0, $this->total_general - $this->acompte_total),
        );
    }
}
