<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ANN-1 — Annonce d'un designer (Espace Designer).
 *
 * Le statut n'est pas stocké : il se DÉDUIT des dates, ce qui évite toute
 * désynchronisation (une annonce ne peut pas rester « en cours » après sa fin
 * faute de tâche planifiée). Référence de temps : heure de Cotonou.
 */
class Annonce extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'annonces';

    /** Fuseau de référence (Bénin, UTC+1 sans heure d'été). */
    public const FUSEAU = 'Africa/Porto-Novo';

    /** Durée de diffusion autorisée, en jours. */
    public const DUREE_MIN = 1;
    public const DUREE_MAX = 10;

    /** Durées de Boost proposées, en jours. */
    public const DUREES_BOOST = [1, 3, 7];

    protected $fillable = [
        'atelier_id', 'titre', 'message', 'image_path', 'image_url',
        'date_debut', 'duree_jours', 'date_fin',
        'boost_actif', 'boost_debut', 'boost_duree_jours', 'boost_fin',
        'boost_prix_xof', 'boost_paye_at',
        'signalements_count', 'signale_at', 'masquee_at', 'motif_masquage',
    ];

    protected $casts = [
        'date_debut'        => 'date',
        'date_fin'          => 'date',
        'duree_jours'       => 'integer',
        'boost_actif'       => 'boolean',
        'boost_debut'       => 'date',
        'boost_fin'         => 'date',
        'boost_duree_jours' => 'integer',
        'boost_prix_xof'    => 'integer',
        'boost_paye_at'     => 'datetime',
        'signalements_count' => 'integer',
        'signale_at'        => 'datetime',
        'masquee_at'        => 'datetime',
    ];

    protected $appends = ['statut', 'boost_en_cours'];

    public function atelier(): BelongsTo
    {
        return $this->belongsTo(Atelier::class);
    }

    /** Date du jour dans le fuseau de référence. */
    public static function aujourdhui(): CarbonImmutable
    {
        return CarbonImmutable::now(self::FUSEAU)->startOfDay();
    }

    /** Le Boost est-il en cours aujourd'hui ? (payé + fenêtre en cours) */
    protected function boostEnCours(): Attribute
    {
        return Attribute::make(get: function () {
            if (! $this->boost_actif || ! $this->boost_debut || ! $this->boost_fin) {
                return false;
            }
            // Comparaison sur la DATE (chaîne Y-m-d) et non sur des datetimes :
            // minuit à Cotonou (UTC+1) précède minuit UTC, ce qui faussait le calcul.
            $jour = self::aujourdhui()->toDateString();

            return $jour >= $this->boost_debut->toDateString()
                && $jour <= $this->boost_fin->toDateString();
        });
    }

    /**
     * Statut affiché : programmee | en_cours | boostee | terminee.
     * « boostee » prime sur « en_cours » pour la mise en avant côté interface.
     */
    protected function statut(): Attribute
    {
        return Attribute::make(get: function () {
            // ANN-10 : une annonce masquée par l'administration l'emporte sur tout.
            if ($this->masquee_at) {
                return 'masquee';
            }

            $jour = self::aujourdhui()->toDateString();

            if ($jour < $this->date_debut->toDateString()) {
                return 'programmee';
            }
            if ($jour > $this->date_fin->toDateString()) {
                return 'terminee';
            }

            return $this->boost_en_cours ? 'boostee' : 'en_cours';
        });
    }

    /** Annonces diffusables aujourd'hui (fenêtre de diffusion en cours). */
    public function scopeActives(Builder $q): Builder
    {
        $jour = self::aujourdhui()->toDateString();

        return $q->whereNull('masquee_at')   // ANN-10 : jamais de diffusion si masquée
            ->whereDate('date_debut', '<=', $jour)->whereDate('date_fin', '>=', $jour);
    }

    /** Boostées d'abord, puis les plus récentes (ordre de la bande défilante). */
    public function scopeOrdreDiffusion(Builder $q): Builder
    {
        return $q->orderByDesc('boost_actif')->orderByDesc('created_at');
    }
}
