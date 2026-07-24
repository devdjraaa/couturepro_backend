<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\OperationCaisse;
use App\Models\Proprietaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Journal de caisse — mouvements d'espèces réels.
 *
 * La caisse ne faisait que dériver les paiements de commandes (lecture seule) —
 * « pas fonctionnelle du tout » (direction). Ces tests verrouillent la brique
 * qui la rend fonctionnelle : entrées/sorties, solde exact, isolation, et
 * l'accès réservé aux plans qui incluent la caisse.
 */
class CaisseJournalTest extends TestCase
{
    use RefreshDatabase;

    private function atelierAvecCaisse(bool $avecCaisse = true): array
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local', 'nom' => 'Sossou', 'prenom' => 'Awa',
            'question_secrete' => 'q', 'reponse_secrete' => 'r', 'password' => bcrypt('x'),
        ]);
        $a = Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Atelier Awa',
            'type' => 'artisan', 'is_maitre' => true, 'statut' => 'actif',
        ]);
        Abonnement::create([
            'atelier_id' => $a->id, 'niveau_cle' => 'atelier_mensuel', 'statut' => 'actif',
            'jours_restants' => 30, 'timestamp_debut' => now(), 'timestamp_expiration' => now()->addDays(30),
            'config_snapshot' => ['module_caisse' => $avecCaisse],
        ]);

        return [$p, $a];
    }

    public function test_le_solde_reflete_les_entrees_moins_les_sorties(): void
    {
        [$p] = $this->atelierAvecCaisse();
        $act = $this->actingAs($p, 'sanctum');

        $act->postJson('/api/caisse/operations', ['type' => 'entree', 'montant' => 10000, 'motif' => 'Apport'])->assertCreated();
        $act->postJson('/api/caisse/operations', ['type' => 'entree', 'montant' => 5000,  'motif' => 'Vente directe'])->assertCreated();
        $act->postJson('/api/caisse/operations', ['type' => 'sortie', 'montant' => 3000,  'motif' => 'Achat tissu'])->assertCreated();

        $r = $act->getJson('/api/caisse/operations')->assertOk();
        $r->assertJsonPath('solde', 12000);        // 10000 + 5000 - 3000
        $r->assertJsonPath('entrees_mois', 15000);
        $r->assertJsonPath('sorties_mois', 3000);
    }

    public function test_supprimer_un_mouvement_corrige_le_solde(): void
    {
        [$p] = $this->atelierAvecCaisse();
        $act = $this->actingAs($p, 'sanctum');

        $id = $act->postJson('/api/caisse/operations', ['type' => 'entree', 'montant' => 8000, 'motif' => 'Erreur'])
                  ->assertCreated()->json('id');

        $act->deleteJson("/api/caisse/operations/{$id}")->assertOk();
        $act->getJson('/api/caisse/operations')->assertOk()->assertJsonPath('solde', 0);
    }

    public function test_un_atelier_ne_supprime_pas_le_mouvement_d_un_autre(): void
    {
        [$p1] = $this->atelierAvecCaisse();
        [, $a2] = $this->atelierAvecCaisse();
        $op = OperationCaisse::create(['atelier_id' => $a2->id, 'type' => 'entree', 'montant' => 100, 'motif' => 'x']);

        // Le propriétaire du 1er atelier ne doit pas atteindre l'opération du 2e.
        $this->actingAs($p1, 'sanctum')->deleteJson("/api/caisse/operations/{$op->id}")->assertNotFound();
        $this->assertDatabaseHas('operations_caisse', ['id' => $op->id]);
    }

    public function test_la_caisse_est_reservee_aux_plans_qui_l_incluent(): void
    {
        [$p] = $this->atelierAvecCaisse(avecCaisse: false);

        $this->actingAs($p, 'sanctum')
             ->postJson('/api/caisse/operations', ['type' => 'entree', 'montant' => 1000, 'motif' => 'x'])
             ->assertStatus(403);
    }

    public function test_un_montant_invalide_est_refuse(): void
    {
        [$p] = $this->atelierAvecCaisse();
        $this->actingAs($p, 'sanctum')
             ->postJson('/api/caisse/operations', ['type' => 'entree', 'montant' => 0, 'motif' => 'x'])
             ->assertStatus(422);
    }
}
