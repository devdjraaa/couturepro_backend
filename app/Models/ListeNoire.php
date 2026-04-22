<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeNoire extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'liste_noire';

    protected $fillable = [
        'type',
        'valeur',
        'raison',
        'admin_id',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public static function estBloque(string $type, string $valeur): bool
    {
        return static::where('type', $type)->where('valeur', $valeur)->exists();
    }
}
