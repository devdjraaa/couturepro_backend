<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Vetement extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'atelier_id',
        'nom',
        'image_path',
        'images',
        'template_numero',
        'is_systeme',
        'is_archived',
        'created_by',
        'created_by_role',
    ];

    protected $appends = ['image_url', 'images_urls'];

    protected $casts = [
        'is_systeme'  => 'boolean',
        'is_archived' => 'boolean',
        'images'      => 'array',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path ? Storage::url($this->image_path) : null,
        );
    }

    protected function imagesUrls(): Attribute
    {
        return Attribute::make(
            get: function () {
                $paths = $this->images ?? ($this->image_path ? [$this->image_path] : []);
                return array_values(array_map(fn ($p) => Storage::url($p), $paths));
            },
        );
    }

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function commandes(): HasMany
    {
        return $this->hasMany(Commande::class, 'vetement_id');
    }

    public function scopeSysteme($query)
    {
        return $query->where('is_systeme', true);
    }

    public function scopeActif($query)
    {
        return $query->where('is_archived', false);
    }
}
