<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ParametresAtelier extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'parametres_atelier';

    protected $fillable = [
        'atelier_id',
        'langue',
        'devise',
        'unite_mesure',
        'theme',
        'mode_sync_photos',
        'multi_ateliers_actif',
        'format_facture',
        'facture_logo_path',
        'facture_ifu',
        'facture_rccm',
        'facture_pied_page',
        'assujetti_tva',
        'emecef_token',
    ];

    protected $appends = ['facture_logo_url'];

    protected $casts = [
        'multi_ateliers_actif' => 'boolean',
        'assujetti_tva'        => 'boolean',
        'emecef_token'         => 'encrypted',
    ];

    protected $hidden = ['emecef_token'];

    protected function factureLogoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->facture_logo_path ? url(Storage::url($this->facture_logo_path)) : null,
        );
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
