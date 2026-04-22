<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotaMensuel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'quotas_mensuels';

    protected $fillable = [
        'atelier_id',
        'annee',
        'mois',
        'nb_clients_crees',
        'nb_commandes_creees',
        'nb_photos_vip',
        'nb_factures_envoyees',
    ];

    protected $casts = [
        'annee'                => 'integer',
        'mois'                 => 'integer',
        'nb_clients_crees'     => 'integer',
        'nb_commandes_creees'  => 'integer',
        'nb_photos_vip'        => 'integer',
        'nb_factures_envoyees' => 'integer',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public static function courant(string $atelierId): self
    {
        return static::firstOrCreate(
            ['atelier_id' => $atelierId, 'annee' => now()->year, 'mois' => now()->month],
            ['nb_clients_crees' => 0, 'nb_commandes_creees' => 0, 'nb_photos_vip' => 0, 'nb_factures_envoyees' => 0]
        );
    }
}
