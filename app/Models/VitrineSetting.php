<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VitrineSetting extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'vitrine_settings';

    protected $fillable = ['cle', 'valeur'];

    protected $casts = ['valeur' => 'array'];

    /**
     * Offres de sponsorisation (mise en avant vitrine), config-driven et
     * éditables depuis l'admin. Valeurs par défaut si rien n'est configuré.
     */
    public static function sponsorisation(): array
    {
        $cfg = static::where('cle', 'sponsorisation')->value('valeur');

        return $cfg ?: [
            'actif'  => true,
            'offres' => [
                ['jours' => 7,  'prix' => 1500],
                ['jours' => 15, 'prix' => 2500],
                ['jours' => 30, 'prix' => 4500],
            ],
        ];
    }
}
