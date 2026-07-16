<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

// P204 : partenaire affiché sur la vitrine.
class Partenaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom', 'categorie', 'logo_path', 'description', 'site_url', 'pays', 'actif', 'is_cle', 'ordre',
    ];

    protected $casts = [
        'actif'  => 'boolean',
        'is_cle' => 'boolean',
        'ordre'  => 'integer',
    ];

    protected $appends = ['logo_url'];

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->logo_path ? url(Storage::url($this->logo_path)) : null,
        );
    }
}
