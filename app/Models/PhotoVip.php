<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhotoVip extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'photos_vip';

    protected $fillable = [
        'atelier_id',
        'uploaded_by',
        'file_path',
        'file_url',
        'nom',
        'taille_octets',
    ];

    protected $casts = [
        'taille_octets' => 'integer',
    ];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
