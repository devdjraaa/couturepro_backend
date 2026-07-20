<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Atelier;
use App\Models\NotificationSysteme;
use App\Models\Proprietaire;
use App\Services\InfosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CLI-2 — « Gextimo Infos ».
 *
 * Deux garanties valent d'être tenues par des tests :
 *
 * 1. Le CIBLAGE. Une info réservée aux comptes Designer ne doit pas atteindre
 *    un artisan. Filtrer côté écran ne suffirait pas — le message partirait
 *    quand même et se lirait dans les outils du navigateur.
 *
 * 2. L'ÉTAT DE LECTURE PAR ATELIER. Le socle existant porte `is_read` sur la
 *    ligne diffusée : le premier atelier qui lisait une annonce la marquait lue
 *    pour tout le monde. C'est le défaut que la table `infos_lectures` corrige,
 *    et une régression ici serait invisible à l'œil nu.
 */
class GextimoInfosTest extends TestCase
{
    use RefreshDatabase;

    private function atelier(string $type = 'artisan', ?string $ville = null): Atelier
    {
        $proprietaire = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local',
            'nom' => 'Test', 'prenom' => 'Infos',
            'question_secrete' => 'q', 'reponse_secrete' => 'r',
            'password' => bcrypt('motdepasse'),
        ]);

        return Atelier::create([
            'proprietaire_id' => $proprietaire->id,
            'nom'             => 'Atelier ' . Str::random(5),
            'type'            => $type,
            'ville'           => $ville,
            // `getAtelier()` passe par `atelierMaitre` : sans ce drapeau, aucun
            // atelier n'est résolu et les appels HTTP ne voient rien.
            'is_maitre'       => true,
        ]);
    }

    private function info(array $cible, array $extra = []): NotificationSysteme
    {
        return NotificationSysteme::create(array_merge([
            'canal'     => 'info',
            'titre'     => 'Titre',
            'contenu'   => 'Contenu',
            'type'      => 'annonce',
            'categorie' => 'annonce',
            'cible'     => $cible,
            'is_read'   => false,
        ], $extra));
    }

    private function service(): InfosService
    {
        return app(InfosService::class);
    }

    public function test_une_info_ciblee_n_atteint_pas_les_autres(): void
    {
        $designer = $this->atelier('designer');
        $artisan  = $this->atelier('artisan');
        $info = $this->info(['mode' => 'types_compte', 'valeurs' => ['designer']]);

        $this->assertTrue($this->service()->concerne($info, $designer));
        $this->assertFalse($this->service()->concerne($info, $artisan),
            'une info réservée aux designers ne doit pas atteindre un artisan');
    }

    public function test_une_cible_sans_valeur_ne_diffuse_a_personne(): void
    {
        $atelier = $this->atelier('designer');
        $info = $this->info(['mode' => 'types_compte', 'valeurs' => []]);

        // Le piège serait de retomber sur « tout le monde » : une annonce
        // réservée partirait à toute la base sur un simple oubli de saisie.
        $this->assertFalse($this->service()->concerne($info, $atelier));
    }

    public function test_absence_de_ciblage_vaut_tout_le_monde(): void
    {
        $atelier = $this->atelier('artisan');

        $this->assertTrue($this->service()->concerne($this->info([]), $atelier));
        $this->assertTrue($this->service()->concerne($this->info(['mode' => 'tous']), $atelier));
    }

    public function test_un_mode_inconnu_ne_diffuse_pas(): void
    {
        $atelier = $this->atelier();
        $info = $this->info(['mode' => 'fantaisie', 'valeurs' => ['x']]);

        $this->assertFalse($this->service()->concerne($info, $atelier));
    }

    public function test_le_ciblage_par_ville_ignore_la_casse(): void
    {
        $atelier = $this->atelier('artisan', 'Cotonou');
        $info = $this->info(['mode' => 'villes', 'valeurs' => ['cotonou']]);

        $this->assertTrue($this->service()->concerne($info, $atelier));
    }

    public function test_la_lecture_est_propre_a_chaque_atelier(): void
    {
        $a = $this->atelier();
        $b = $this->atelier();
        $info = $this->info(['mode' => 'tous']);

        $this->service()->marquerLue($info, $a);

        $this->assertTrue($this->service()->pourAtelier($a)->first()->lu);
        $this->assertFalse($this->service()->pourAtelier($b)->first()->lu,
            "la lecture d'un atelier ne doit pas masquer l'info pour les autres");
    }

    public function test_marquer_lue_deux_fois_ne_doublonne_pas(): void
    {
        $a = $this->atelier();
        $info = $this->info(['mode' => 'tous']);

        $this->service()->marquerLue($info, $a);
        $this->service()->marquerLue($info, $a);

        $this->assertDatabaseCount('infos_lectures', 1);
    }

    public function test_une_info_programmee_n_apparait_pas_avant_l_heure(): void
    {
        $a = $this->atelier();
        $this->info(['mode' => 'tous'], ['publie_at' => now()->addDay()]);

        $this->assertCount(0, $this->service()->pourAtelier($a));
    }

    public function test_une_info_expiree_disparait(): void
    {
        $a = $this->atelier();
        $this->info(['mode' => 'tous'], ['publie_at' => now()->subDays(3), 'expire_at' => now()->subDay()]);

        $this->assertCount(0, $this->service()->pourAtelier($a));
    }

    public function test_les_epinglees_passent_devant(): void
    {
        $a = $this->atelier();
        $this->info(['mode' => 'tous'], ['titre' => 'Ancienne', 'publie_at' => now()->subHour()]);
        $this->info(['mode' => 'tous'], ['titre' => 'Épinglée', 'epingle' => true, 'publie_at' => now()->subDays(5)]);

        $this->assertSame('Épinglée', $this->service()->pourAtelier($a)->first()->titre);
    }

    public function test_le_compteur_ne_compte_que_les_infos_concernees(): void
    {
        $artisan = $this->atelier('artisan');
        $this->info(['mode' => 'tous']);
        $this->info(['mode' => 'types_compte', 'valeurs' => ['designer']]);

        $this->assertSame(1, $this->service()->nonLues($artisan));
    }

    // ── Aller-retour complet : l'admin diffuse, le pro reçoit ────────────

    private function admin(): Admin
    {
        return Admin::create([
            'nom' => 'Admin', 'prenom' => 'Infos',
            'email' => Str::uuid() . '@test.local',
            'password' => bcrypt('motdepasse'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_ce_que_l_admin_diffuse_arrive_bien_au_professionnel(): void
    {
        $atelier = $this->atelier('designer');

        $this->actingAs($this->admin(), 'admin')
            ->postJson('/api/admin/infos', [
                'titre' => 'Nouveauté Designer', 'contenu' => 'Un nouvel outil est disponible.',
                'categorie' => 'nouveaute', 'lien' => null, 'epingle' => false,
                'publie_at' => null, 'expire_at' => null,
                'cible' => ['mode' => 'types_compte', 'valeurs' => ['designer']],
            ])
            ->assertCreated();

        $reponse = $this->actingAs($atelier->proprietaire, 'sanctum')
            ->getJson('/api/infos')
            ->assertOk();

        $this->assertCount(1, $reponse->json('data'));
        $this->assertSame('Nouveauté Designer', $reponse->json('data.0.titre'));
        // Les catégories voyagent avec la liste : l'écran n'a aucune
        // correspondance libellé/couleur à maintenir en dur.
        $this->assertNotEmpty($reponse->json('categories'));
    }

    public function test_un_atelier_hors_cible_ne_recoit_rien(): void
    {
        $artisan = $this->atelier('artisan');

        $this->actingAs($this->admin(), 'admin')
            ->postJson('/api/admin/infos', [
                'titre' => 'Réservé', 'contenu' => 'Designers uniquement.',
                'categorie' => 'nouveaute', 'lien' => null, 'epingle' => false,
                'publie_at' => null, 'expire_at' => null,
                'cible' => ['mode' => 'types_compte', 'valeurs' => ['designer']],
            ])->assertCreated();

        $this->actingAs($artisan->proprietaire, 'sanctum')
            ->getJson('/api/infos')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_marquer_lue_une_info_qui_ne_nous_est_pas_destinee_echoue(): void
    {
        $artisan = $this->atelier('artisan');
        $info = $this->info(['mode' => 'types_compte', 'valeurs' => ['designer']]);

        // Sans ce contrôle, l'existence de messages ciblés fuirait par l'API :
        // un 200 confirmerait qu'une info réservée existe bien à cet identifiant.
        $this->actingAs($artisan->proprietaire, 'sanctum')
            ->postJson("/api/infos/{$info->id}/lue")
            ->assertNotFound();
    }

    public function test_une_categorie_inconnue_est_refusee(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->postJson('/api/admin/infos', [
                'titre' => 'X', 'contenu' => 'Y', 'categorie' => 'fantaisie',
                'lien' => null, 'epingle' => false, 'publie_at' => null, 'expire_at' => null,
                'cible' => ['mode' => 'tous', 'valeurs' => []],
            ])
            ->assertStatus(422);
    }
}
