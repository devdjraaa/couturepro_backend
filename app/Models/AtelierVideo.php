<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// PL-7 : vidéo de présentation d'un atelier (lien).
class AtelierVideo extends Model
{
    use HasFactory;

    protected $table = 'atelier_videos';

    protected $fillable = ['atelier_id', 'titre', 'url', 'position'];

    protected $casts = ['position' => 'integer'];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class, 'atelier_id');
    }
}
