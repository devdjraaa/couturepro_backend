<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = ['atelier_id', 'nom', 'annonce_message', 'annonce_at'];

    protected $casts = ['annonce_at' => 'datetime'];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }

    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class, 'collection_id');
    }
}
