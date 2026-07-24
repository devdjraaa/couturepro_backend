<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Proprietaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Workflow de suivi d'une commande (étape : commande→coupe→confection→essayage→livraison).
 *
 * L'étape est distincte du statut (en_cours|livre|annule) : c'est pourquoi
 * l'onglet « Essayage » de la liste, qui filtrait sur le statut, ne renvoyait
 * jamais rien. Ces tests verrouillent la transition d'étape côté serveur :
 * une étape valide est persistée, une étape inconnue est refusée, et un atelier
 * ne touche pas la commande d'un autre.
 */
class CommandeEtapeTest extends TestCase
{
    use RefreshDatabase;

    private function commandePour(Proprietaire $p, Atelier $a): Commande
    {
        $client = Client::create([
            'atelier_id' => $a->id, 'nom' => 'Client', 'prenom' => 'Test',
            'created_by' => $p->id, 'created_by_role' => 'proprietaire',
        ]);

        return Commande::create([
            'atelier_id' => $a->id, 'client_id' => $client->id,
            'created_by' => $p->id, 'created_by_role' => 'proprietaire',
            'quantite' => 1, 'prix' => 10000, 'acompte' => 0,
        ]);
    }

    private function proprietaireAtelier(): array
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local', 'nom' => 'Zinsou', 'prenom' => 'Koffi',
            'question_secrete' => 'q', 'reponse_secrete' => 'r', 'password' => bcrypt('x'),
        ]);
        $a = Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Atelier',
            'type' => 'artisan', 'is_maitre' => true, 'statut' => 'actif',
        ]);
        Abonnement::create([
            'atelier_id' => $a->id, 'niveau_cle' => 'atelier_mensuel', 'statut' => 'actif',
            'jours_restants' => 30, 'timestamp_debut' => now(), 'timestamp_expiration' => now()->addDays(30),
            'config_snapshot' => [],
        ]);

        return [$p, $a];
    }

    public function test_une_commande_demarre_a_l_etape_commande(): void
    {
        [$p, $a] = $this->proprietaireAtelier();
        $this->assertSame('commande', $this->commandePour($p, $a)->etape);
    }

    public function test_avancer_a_l_etape_essayage_est_persiste(): void
    {
        [$p, $a] = $this->proprietaireAtelier();
        $cmd = $this->commandePour($p, $a);

        $this->actingAs($p, 'sanctum')
             ->postJson("/api/commandes/{$cmd->id}/etape", ['etape' => 'essayage'])
             ->assertOk()
             ->assertJsonPath('etape', 'essayage');

        $this->assertSame('essayage', $cmd->fresh()->etape);
    }

    public function test_une_etape_inconnue_est_refusee(): void
    {
        [$p, $a] = $this->proprietaireAtelier();
        $cmd = $this->commandePour($p, $a);

        $this->actingAs($p, 'sanctum')
             ->postJson("/api/commandes/{$cmd->id}/etape", ['etape' => 'expedition'])
             ->assertStatus(422);
    }

    public function test_un_atelier_ne_change_pas_l_etape_d_une_autre_commande(): void
    {
        [$p1] = $this->proprietaireAtelier();
        [$p2, $a2] = $this->proprietaireAtelier();
        $cmd2 = $this->commandePour($p2, $a2);

        $this->actingAs($p1, 'sanctum')
             ->postJson("/api/commandes/{$cmd2->id}/etape", ['etape' => 'coupe'])
             ->assertForbidden();

        $this->assertSame('commande', $cmd2->fresh()->etape);
    }
}
