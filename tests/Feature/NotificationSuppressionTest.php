<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\NotificationSysteme;
use App\Models\Proprietaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Suppression des notifications (POST /notifications/delete).
 *
 * L'endpoint existait mais aucun écran ne l'appelait : on ne pouvait que
 * « marquer lu », jamais effacer. Ces tests verrouillent la suppression par
 * identifiants et le vidage complet, avec l'isolation par atelier.
 */
class NotificationSuppressionTest extends TestCase
{
    use RefreshDatabase;

    private function proprietaireAvecNotifs(int $n = 3): array
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local', 'nom' => 'Dossou', 'prenom' => 'Ama',
            'question_secrete' => 'q', 'reponse_secrete' => 'r', 'password' => bcrypt('x'),
        ]);
        $a = Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Atelier Ama',
            'type' => 'artisan', 'is_maitre' => true, 'statut' => 'actif',
        ]);
        Abonnement::create([
            'atelier_id' => $a->id, 'niveau_cle' => 'atelier_mensuel', 'statut' => 'actif',
            'jours_restants' => 30, 'timestamp_debut' => now(), 'timestamp_expiration' => now()->addDays(30),
            'config_snapshot' => [],
        ]);
        $notifs = collect(range(1, $n))->map(fn ($i) => NotificationSysteme::create([
            'atelier_id' => $a->id, 'titre' => "Notif $i", 'contenu' => '...',
            'type' => 'systeme', 'is_read' => false,
        ]));

        return [$p, $a, $notifs];
    }

    public function test_supprimer_par_identifiants(): void
    {
        [$p, $a, $notifs] = $this->proprietaireAvecNotifs(3);

        $this->actingAs($p, 'sanctum')
             ->postJson('/api/notifications/delete', ['ids' => [$notifs[0]->id]])
             ->assertOk();

        $this->assertDatabaseMissing('notifications_systeme', ['id' => $notifs[0]->id]);
        $this->assertSame(2, NotificationSysteme::where('atelier_id', $a->id)->count());
    }

    public function test_vider_toutes(): void
    {
        [$p, $a] = $this->proprietaireAvecNotifs(3);

        $this->actingAs($p, 'sanctum')
             ->postJson('/api/notifications/delete', ['all' => true])
             ->assertOk();

        $this->assertSame(0, NotificationSysteme::where('atelier_id', $a->id)->count());
    }

    public function test_un_atelier_ne_supprime_pas_les_notifs_d_un_autre(): void
    {
        [$p1] = $this->proprietaireAvecNotifs(2);
        [, $a2, $notifs2] = $this->proprietaireAvecNotifs(2);

        // Le proprio 1 tente de supprimer une notif du proprio 2 : sans effet.
        $this->actingAs($p1, 'sanctum')
             ->postJson('/api/notifications/delete', ['ids' => [$notifs2[0]->id]])
             ->assertOk();

        $this->assertDatabaseHas('notifications_systeme', ['id' => $notifs2[0]->id]);
        $this->assertSame(2, NotificationSysteme::where('atelier_id', $a2->id)->count());
    }
}
