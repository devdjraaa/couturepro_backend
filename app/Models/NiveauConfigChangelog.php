<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NiveauConfigChangelog extends Model
{
    use HasFactory;

    protected $table = 'niveaux_config_changelog';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false; // append-only, created_at uniquement

    protected $fillable = [
        'niveau_cle',
        'admin_id',
        'champ_modifie',
        'ancienne_valeur',
        'nouvelle_valeur',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
