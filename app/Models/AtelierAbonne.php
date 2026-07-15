<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// P173 : abonné anonyme d'un créateur (« S'abonner / Enregistrer »).
class AtelierAbonne extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'atelier_abonnes';

    protected $fillable = ['atelier_id', 'visitor_key'];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
