<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// P204 : candidature « Devenir partenaire ».
class CandidaturePartenaire extends Model
{
    use HasFactory;

    protected $table = 'candidatures_partenaires';

    protected $fillable = [
        'nom_organisation', 'pays_region', 'categorie_souhaitee', 'type_apport',
        'contact_nom', 'contact_email', 'contact_telephone', 'message', 'statut',
    ];
}
