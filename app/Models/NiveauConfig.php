<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NiveauConfig extends Model
{
    use HasFactory;

    protected $table = 'niveaux_config';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'cle',
        'label',
        'label_en',
        'type_compte',
        'visible_vitrine',
        'visible_app',
        'duree_jours',
        'prix_xof',
        'prix_mensuel_equivalent_xof',
        'config',
        'is_actif',
        'ordre_affichage',
        'description_courte',
        'description_courte_en',
        'updated_by',
    ];

    protected $casts = [
        'config'                        => 'array',
        'prix_xof'                      => 'decimal:2',
        'prix_mensuel_equivalent_xof'   => 'decimal:2',
        'is_actif'                      => 'boolean',
        'visible_vitrine'               => 'boolean',
        'visible_app'                   => 'boolean',
        'duree_jours'                   => 'integer',
        'ordre_affichage'               => 'integer',
    ];

    /**
     * Plan servi pendant la période d'essai selon le type d'atelier.
     * L'essai promet un « accès complet » : un designer a le niveau Studio (clé
     * historique master_mensuel), un artisan le niveau Atelier.
     *
     * L'artisan recevait `standard_mensuel` — un plan LEGACY DÉSACTIVÉ. Effet
     * de bord invisible mais lourd : la direction pouvait modifier le plan
     * « Atelier » dans l'administration sans que cela change quoi que ce soit
     * pour un seul artisan, puisqu'aucun n'y était rattaché. Côté designer tout
     * marchait, d'où le diagnostic « on ne peut modifier que les plans
     * designer ».
     */
    public static function cleEssaiPour(?string $typeAtelier): string
    {
        return $typeAtelier === 'designer' ? 'master_mensuel' : 'atelier_mensuel';
    }

    /** Plan facturé à l'année (échéance de date à date : +1 an). */
    public function estAnnuel(): bool
    {
        return (int) $this->duree_jours >= 360 && ! $this->estPermanent();
    }

    /** Plan « permanent » (gratuit — durée symbolique très longue, pas d'échéance réelle). */
    public function estPermanent(): bool
    {
        return (int) $this->duree_jours >= 1000;
    }

    /**
     * Échéance « de date à date » depuis $depuis (spec upgrade direction 16/07/2026) :
     * mensuel → même jour du mois suivant, annuel → même date l'année suivante,
     * jamais un nombre fixe de jours calendaires. Sans débordement de fin de mois.
     */
    public function prochaineEcheance(\Carbon\CarbonInterface $depuis): \Carbon\CarbonInterface
    {
        if ($this->estPermanent()) {
            return $depuis->copy()->addDays((int) $this->duree_jours);
        }

        return $this->estAnnuel()
            ? $depuis->copy()->addYearsNoOverflow(1)
            : $depuis->copy()->addMonthsNoOverflow(1);
    }

    // Relations
    public function abonnements(): HasMany
    {
        return $this->hasMany(Abonnement::class, 'niveau_cle', 'cle');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'niveau_cle', 'cle');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(TransactionAbonnement::class, 'niveau_cle', 'cle');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'updated_by');
    }

    // Scopes
    public function scopeActif($query)
    {
        return $query->where('is_actif', true)->orderBy('ordre_affichage');
    }
}
