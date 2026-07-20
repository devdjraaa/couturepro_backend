<?php

namespace Tests\Feature;

use App\Models\GxtClient;
use App\Models\NotificationClient;
use App\Support\MessagesCommandeClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Pt 24 — Notifications du client final.
 *
 * Deux garanties méritent des tests :
 *
 * 1. L'ISOLATION. Un client ne doit ni lire ni marquer lues les notifications
 *    d'un autre. Un 200 sur l'identifiant d'autrui confirmerait au passage son
 *    existence.
 *
 * 2. LA SOURCE UNIQUE des textes. L'e-mail et la notification dans
 *    l'application doivent dire rigoureusement la même chose — c'est la leçon
 *    du partage WhatsApp, où deux constructeurs concurrents produisaient des
 *    contenus différents selon le chemin emprunté.
 */
class NotificationsClientTest extends TestCase
{
    use RefreshDatabase;

    private function client(): GxtClient
    {
        return GxtClient::create(['email' => Str::uuid() . '@test.local']);
    }

    private function notif(GxtClient $c, array $extra = []): NotificationClient
    {
        return NotificationClient::create(array_merge([
            'gxt_client_id' => $c->id,
            'type'          => 'coupe',
            'titre'         => 'Commande CMD-1 : la coupe a commencé',
            'contenu'       => 'Bonne nouvelle.',
        ], $extra));
    }

    public function test_un_client_ne_voit_que_ses_notifications(): void
    {
        $a = $this->client();
        $b = $this->client();
        $this->notif($a);
        $this->notif($b);
        $this->notif($b);

        $this->actingAs($a, 'sanctum')
            ->getJson('/api/vitrine/client/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_marquer_lue_la_notification_d_un_autre_echoue(): void
    {
        $a = $this->client();
        $b = $this->client();
        $notifDeB = $this->notif($b);

        $this->actingAs($a, 'sanctum')
            ->postJson("/api/vitrine/client/notifications/{$notifDeB->id}/lue")
            ->assertNotFound();

        // Et elle doit rester non lue : un refus ne doit rien modifier.
        $this->assertNull($notifDeB->fresh()->lu_at);
    }

    public function test_le_compteur_ne_compte_que_les_non_lues(): void
    {
        $c = $this->client();
        $this->notif($c);
        $this->notif($c, ['lu_at' => now()]);

        $this->actingAs($c, 'sanctum')
            ->getJson('/api/vitrine/client/notifications/compteur')
            ->assertOk()
            ->assertJsonPath('non_lues', 1);
    }

    public function test_tout_marquer_lu_ne_touche_pas_les_autres_clients(): void
    {
        $a = $this->client();
        $b = $this->client();
        $this->notif($a);
        $notifDeB = $this->notif($b);

        $this->actingAs($a, 'sanctum')
            ->postJson('/api/vitrine/client/notifications/tout-lu')
            ->assertOk();

        $this->assertNull($notifDeB->fresh()->lu_at, "les notifications d'un autre client ne doivent pas être touchées");
    }

    public function test_les_non_lues_passent_devant(): void
    {
        $c = $this->client();
        // La lue est la PLUS RÉCENTE : sans le tri par état de lecture, elle
        // apparaîtrait en tête et masquerait ce qui demande attention.
        $this->notif($c, ['titre' => 'Ancienne non lue', 'created_at' => now()->subDay()]);
        $this->notif($c, ['titre' => 'Récente déjà lue', 'lu_at' => now(), 'created_at' => now()]);

        $this->actingAs($c, 'sanctum')
            ->getJson('/api/vitrine/client/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.titre', 'Ancienne non lue');
    }

    public function test_e_mail_et_notification_disent_la_meme_chose(): void
    {
        // La garantie qui empêche les deux canaux de diverger avec le temps.
        foreach (MessagesCommandeClient::EVENEMENTS as $evenement) {
            $m = MessagesCommandeClient::pour($evenement, 'CMD-1', 'Atelier Kofi');

            $this->assertNotEmpty($m['titre']);
            $this->assertNotEmpty($m['resume']);
            $this->assertStringContainsString($m['resume'], $m['corps'],
                "le corps de l'e-mail doit contenir le texte affiché dans l'application");
        }
    }

    public function test_un_evenement_inconnu_donne_un_message_neutre(): void
    {
        $m = MessagesCommandeClient::pour('fantaisie', 'CMD-1', 'Atelier Kofi');

        // Jamais de chaîne vide ni d'erreur : un événement non prévu doit
        // produire un message compréhensible, pas une notification muette.
        $this->assertStringContainsString('CMD-1', $m['titre']);
        $this->assertNotEmpty($m['resume']);
    }
}
