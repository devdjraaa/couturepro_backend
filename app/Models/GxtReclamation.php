<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// P202 / Espace Client v3 — réclamation d'un client vitrine sur une commande.
class GxtReclamation extends Model
{
    use HasUuids;

    protected $table = 'gxt_reclamations';

    protected $fillable = ['gxt_client_id', 'commande_id', 'atelier_id', 'sujet', 'statut', 'resolue_at'];

    protected $casts = ['resolue_at' => 'datetime'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(GxtClient::class, 'gxt_client_id');
    }

    public function commande(): BelongsTo
    {
        return $this->belongsTo(Commande::class, 'commande_id');
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(GxtReclamationMessage::class, 'reclamation_id')->orderBy('created_at');
    }
}
