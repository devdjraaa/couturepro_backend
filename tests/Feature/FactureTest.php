<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\Facture;
use App\Models\Proprietaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Facturation (POST /factures) — cœur « argent ».
 *
 * Non couverte jusqu'ici. Ces tests verrouillent : la création d'une facture
 * (numérotée, non payée), le verrou par plan (la facturation est une
 * fonctionnalité de plan) et l'isolation entre ateliers.
 */
class FactureTest extends TestCase
{
    use RefreshDatabase;

    private function proprietaireAvecPlan(bool $facturation = true): array
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local', 'nom' => 'Bio', 'prenom' => 'Ken',
            'question_secrete' => 'q', 'reponse_secrete' => 'r', 'password' => bcrypt('x'),
        ]);
        $a = Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Atelier Ken',
            'type' => 'artisan', 'is_maitre' => true, 'statut' => 'actif',
        ]);
        Abonnement::create([
            'atelier_id' => $a->id, 'niveau_cle' => 'atelier_mensuel', 'statut' => 'actif',
            'jours_restants' => 30, 'timestamp_debut' => now(), 'timestamp_expiration' => now()->addDays(30),
            'config_snapshot' => ['facturation' => $facturation],
        ]);

        return [$p, $a];
    }

    private function payload(): array
    {
        return [
            'type' => 'facture',
            'client_nom' => 'Awa Sossou',
            'lignes' => [
                ['description' => 'Robe wax', 'quantite' => 1, 'prix_unitaire' => 25000],
            ],
        ];
    }

    public function test_creer_une_facture_la_numerote_et_la_marque_non_payee(): void
    {
        [$p, $a] = $this->proprietaireAvecPlan();

        $r = $this->actingAs($p, 'sanctum')
                  ->postJson('/api/factures', $this->payload())
                  ->assertCreated();

        $r->assertJsonPath('statut', 'non_payee');
        $this->assertNotEmpty($r->json('numero'));
        $this->assertSame(1, Facture::where('atelier_id', $a->id)->count());
    }

    public function test_la_facturation_est_reservee_aux_plans_qui_l_incluent(): void
    {
        [$p] = $this->proprietaireAvecPlan(facturation: false);

        $this->actingAs($p, 'sanctum')
             ->postJson('/api/factures', $this->payload())
             ->assertStatus(403);
    }

    public function test_un_atelier_ne_lit_pas_la_facture_d_un_autre(): void
    {
        [$p1] = $this->proprietaireAvecPlan();
        [$p2] = $this->proprietaireAvecPlan();

        $id = $this->actingAs($p2, 'sanctum')
                   ->postJson('/api/factures', $this->payload())
                   ->assertCreated()->json('id');

        // L'accès est refusé (403) : un atelier ne voit pas la facture d'un autre.
        $this->actingAs($p1, 'sanctum')
             ->getJson("/api/factures/{$id}")
             ->assertForbidden();
    }
}
