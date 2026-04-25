<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommunicationsConfig extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'communications_config';

    protected $fillable = [
        'atelier_id',
        'confirmation_commande',
        'rappel_livraison_j2',
        'commande_prete',
        'whatsapp_enabled',
    ];

    protected $casts = [
        'confirmation_commande' => 'boolean',
        'rappel_livraison_j2'   => 'boolean',
        'commande_prete'        => 'boolean',
        'whatsapp_enabled'      => 'boolean',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
