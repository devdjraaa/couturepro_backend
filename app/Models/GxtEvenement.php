<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// P202 / Espace Client v3 — événement métier (immuable, pas d'updated_at).
class GxtEvenement extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'gxt_evenements';

    /** Types d'événements métier acceptés (whitelist de l'ingestion). */
    public const TYPES = [
        'vue_article', 'vue_article_repete', 'vue_profil_designer',
        'ajout_panier', 'retrait_panier', 'ajout_wishlist', 'retrait_wishlist',
        'abandon_panier', 'commande_passee', 'avis_laisse', 'reclamation_ouverte',
        'recherche_effectuee', 'recherche_sans_resultat',
        'clic_partage', 'clic_photo', 'clic_message_designer', 'clic_whatsapp_designer',
    ];

    protected $fillable = [
        'gxt_client_id', 'session_id', 'type', 'article_type', 'article_id',
        'atelier_id', 'commande_id', 'valeur_fcfa', 'duree_secondes',
        'metadata', 'appareil',
    ];

    protected $casts = [
        'metadata'    => 'array',
        'valeur_fcfa' => 'decimal:2',
    ];
}
