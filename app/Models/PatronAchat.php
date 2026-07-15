<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// P162-163 : achat d'un patron ; `code_transaction` = clé de récupération/téléchargement.
class PatronAchat extends Model
{
    use HasUuids;

    protected $table = 'patron_achats';

    protected $fillable = [
        'patron_id', 'paiement_id', 'code_transaction',
        'acheteur_nom', 'acheteur_email', 'acheteur_tel',
        'montant', 'statut', 'nb_telechargements', 'paye_at',
    ];

    protected $casts = [
        'montant'            => 'integer',
        'nb_telechargements' => 'integer',
        'paye_at'            => 'datetime',
    ];

    public function patron(): BelongsTo
    {
        return $this->belongsTo(Patron::class, 'patron_id');
    }

    public function paiement(): BelongsTo
    {
        return $this->belongsTo(Paiement::class, 'paiement_id');
    }

    public function estPaye(): bool
    {
        return $this->statut === 'paye';
    }
}
