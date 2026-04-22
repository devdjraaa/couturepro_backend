<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fonctionnalite extends Model
{
    use HasFactory;

    protected $table = 'fonctionnalites';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'cle',
        'label',
        'description',
        'type',
        'unite',
        'categorie',
        'valeur_defaut',
        'is_actif',
        'ordre_affichage',
    ];

    protected $casts = [
        'is_actif'        => 'boolean',
        'ordre_affichage' => 'integer',
    ];

    public function scopeActif($query)
    {
        return $query->where('is_actif', true)->orderBy('ordre_affichage');
    }
}
