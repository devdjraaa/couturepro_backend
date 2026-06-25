<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandeDevis extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'demandes_devis';

    protected $fillable = ['atelier_id', 'vetement_id', 'nom', 'contact', 'description', 'budget', 'delai', 'statut'];
}
