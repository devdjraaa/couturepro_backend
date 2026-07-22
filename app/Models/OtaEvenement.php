<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Un événement OTA rapporté par un appareil — voir la migration pour le contexte.
 */
class OtaEvenement extends Model
{
    public $timestamps = false;
    protected $table = 'gxt_ota_evenements';

    protected $fillable = ['id', 'atelier_id', 'app_id', 'version', 'evenement', 'detail', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (self $e) {
            $e->id ??= (string) Str::uuid();
        });
    }
}
