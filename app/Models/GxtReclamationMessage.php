<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// P202 / Espace Client v3 — message du fil de discussion d'une réclamation (horodaté).
class GxtReclamationMessage extends Model
{
    use HasUuids;

    protected $table = 'gxt_reclamation_messages';

    protected $fillable = ['reclamation_id', 'auteur_type', 'auteur_id', 'message'];

    public function reclamation(): BelongsTo
    {
        return $this->belongsTo(GxtReclamation::class, 'reclamation_id');
    }
}
