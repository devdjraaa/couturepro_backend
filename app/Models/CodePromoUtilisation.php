<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodePromoUtilisation extends Model
{
    use HasUuids;

    protected $table = 'code_promo_utilisations';

    protected $fillable = [
        'code_promo_id', 'proprietaire_id', 'telephone', 'atelier_id',
    ];

    public function codePromo(): BelongsTo
    {
        return $this->belongsTo(CodePromo::class);
    }

    public function proprietaire(): BelongsTo
    {
        return $this->belongsTo(Proprietaire::class);
    }
}
