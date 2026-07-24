<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\Proprietaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * État d'abonnement courant (GET /abonnement/current).
 *
 * C'est la source de vérité côté client pour ce que le plan autorise (config)
 * et les quotas affichés en jauge. Non couvert jusqu'ici alors que c'est de
 * l'argent : ces tests verrouillent la config renvoyée et l'exposition du
 * quota de nouveaux clients (la jauge de CH-4).
 */
class AbonnementCurrentTest extends TestCase
{
    use RefreshDatabase;

    private function proprietaireAvecPlan(array $config): Proprietaire
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local', 'nom' => 'Agbo', 'prenom' => 'Nadia',
            'question_secrete' => 'q', 'reponse_secrete' => 'r', 'password' => bcrypt('x'),
        ]);
        $a = Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Atelier Nadia',
            'type' => 'artisan', 'is_maitre' => true, 'statut' => 'actif',
        ]);
        Abonnement::create([
            'atelier_id' => $a->id, 'niveau_cle' => 'atelier_mensuel', 'statut' => 'actif',
            'jours_restants' => 30, 'timestamp_debut' => now(), 'timestamp_expiration' => now()->addDays(30),
            'config_snapshot' => $config,
        ]);

        return $p;
    }

    public function test_renvoie_le_statut_et_la_config_du_plan(): void
    {
        $p = $this->proprietaireAvecPlan(['module_caisse' => true]);

        $this->actingAs($p, 'sanctum')
             ->getJson('/api/abonnement/current')
             ->assertOk()
             ->assertJsonPath('statut', 'actif')
             ->assertJsonPath('niveau_cle', 'atelier_mensuel')
             ->assertJsonPath('config.module_caisse', true);
    }

    public function test_le_quota_de_nouveaux_clients_est_expose_quand_le_plan_le_borne(): void
    {
        // Plan gratuit typique : 10 nouveaux clients par mois.
        $p = $this->proprietaireAvecPlan(['max_clients_par_mois' => 10]);

        $this->actingAs($p, 'sanctum')
             ->getJson('/api/abonnement/current')
             ->assertOk()
             ->assertJsonPath('quota_clients.max', 10)
             ->assertJsonPath('quota_clients.utilise', 0);
    }

    public function test_un_plan_illimite_n_expose_pas_de_quota_clients(): void
    {
        $p = $this->proprietaireAvecPlan(['max_clients_par_mois' => -1]);

        $this->actingAs($p, 'sanctum')
             ->getJson('/api/abonnement/current')
             ->assertOk()
             ->assertJsonPath('quota_clients', null);
    }
}
