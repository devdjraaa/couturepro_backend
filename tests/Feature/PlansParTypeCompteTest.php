<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\NiveauConfig;
use App\Models\Proprietaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Public et visibilité d'un plan d'abonnement.
 *
 * Les plans étaient partagés sans que rien ne le dise : le même jeu était
 * proposé à un artisan et à un designer, et l'administration n'indiquait nulle
 * part lequel s'adressait à qui — d'où « je ne vois pas les plans artisan ».
 *
 * Ces tests verrouillent les trois réglages que la direction pilote désormais
 * seule : à qui s'adresse le plan, et sur quelle surface il apparaît.
 */
class PlansParTypeCompteTest extends TestCase
{
    use RefreshDatabase;

    private function plan(string $cle, array $attrs = []): NiveauConfig
    {
        return NiveauConfig::create(array_merge([
            'cle' => $cle, 'label' => ucfirst($cle),
            'duree_jours' => 31, 'prix_xof' => 2500, 'config' => ['max_membres' => 1],
            'is_actif' => true,
        ], $attrs));
    }

    private function proprietaireAvecAtelier(string $type): Proprietaire
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local',
            'nom' => 'Sossou', 'prenom' => 'Awa',
            'question_secrete' => 'q', 'reponse_secrete' => 'r',
            'password' => bcrypt('motdepasse'),
        ]);
        Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Atelier ' . $type,
            'type' => $type, 'is_maitre' => true, 'statut' => 'actif',
        ]);

        return $p;
    }

    public function test_un_artisan_ne_voit_pas_les_plans_reserves_aux_designers(): void
    {
        $this->plan('pour_artisan', ['type_compte' => 'artisan']);
        $this->plan('pour_designer', ['type_compte' => 'designer']);
        $this->plan('pour_tous', ['type_compte' => 'tous']);

        $cles = collect(
            $this->actingAs($this->proprietaireAvecAtelier('artisan'), 'sanctum')
                 ->getJson('/api/abonnement/plans')->assertOk()->json()
        )->pluck('cle');

        $this->assertContains('pour_artisan', $cles);
        $this->assertContains('pour_tous', $cles);
        $this->assertNotContains('pour_designer', $cles);
    }

    public function test_un_designer_ne_voit_pas_les_plans_reserves_aux_artisans(): void
    {
        $this->plan('pour_artisan', ['type_compte' => 'artisan']);
        $this->plan('pour_designer', ['type_compte' => 'designer']);

        $cles = collect(
            $this->actingAs($this->proprietaireAvecAtelier('designer'), 'sanctum')
                 ->getJson('/api/abonnement/plans')->assertOk()->json()
        )->pluck('cle');

        $this->assertContains('pour_designer', $cles);
        $this->assertNotContains('pour_artisan', $cles);
    }

    public function test_le_plan_deja_souscrit_reste_visible_meme_hors_filtre(): void
    {
        $this->plan('pour_designer', ['type_compte' => 'designer']);
        $proprietaire = $this->proprietaireAvecAtelier('artisan');

        // Cas réel : un compte a changé de type après avoir souscrit. Masquer
        // son plan afficherait « plan actuel » sans la carte correspondante.
        Abonnement::create([
            'atelier_id' => $proprietaire->atelierMaitre->id,
            'niveau_cle' => 'pour_designer', 'statut' => 'actif',
            'jours_restants' => 30, 'timestamp_debut' => now(),
            'timestamp_expiration' => now()->addDays(30),
        ]);

        $cles = collect($this->actingAs($proprietaire, 'sanctum')
            ->getJson('/api/abonnement/plans')->assertOk()->json())->pluck('cle');

        $this->assertContains('pour_designer', $cles);
    }

    public function test_un_plan_masque_disparait_de_la_surface_concernee(): void
    {
        $this->plan('appli_seulement',  ['visible_vitrine' => false, 'visible_app' => true]);
        $this->plan('vitrine_seulement', ['visible_vitrine' => true,  'visible_app' => false]);

        $vitrine = collect($this->getJson('/api/vitrine/plans')->assertOk()->json())->pluck('cle');
        $this->assertContains('vitrine_seulement', $vitrine);
        $this->assertNotContains('appli_seulement', $vitrine);

        $appli = collect($this->actingAs($this->proprietaireAvecAtelier('artisan'), 'sanctum')
            ->getJson('/api/abonnement/plans')->assertOk()->json())->pluck('cle');
        $this->assertContains('appli_seulement', $appli);
        $this->assertNotContains('vitrine_seulement', $appli);
    }

    /**
     * Piège évité de justesse : `validate()` ne rend que les clés déclarées, et
     * l'enregistrement écrasait tout le bloc. Un écran qui n'édite que le badge
     * aurait effacé l'essai offert et les libellés au premier enregistrement.
     */
    public function test_enregistrer_une_partie_des_reglages_n_efface_pas_le_reste(): void
    {
        $admin = \App\Models\Admin::create([
            'nom' => 'Admin', 'prenom' => 'Tarifs',
            'email' => Str::uuid() . '@test.local',
            'password' => bcrypt('motdepasse'),
            'role' => 'super_admin', 'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
             ->putJson('/api/admin/vitrine/tarification', [
                 'types_actif' => true, 'note_actif' => true, 'packs_actif' => true,
                 'badge_populaire' => ['fr' => 'Le plus choisi', 'en' => 'Most chosen'],
             ])->assertOk();

        $apres = \App\Models\VitrineSetting::tarification();

        $this->assertSame('Le plus choisi', $apres['badge_populaire']['fr']);
        // Ce qui n'était pas dans la requête doit avoir survécu.
        $this->assertArrayHasKey('libelles', $apres);
        $this->assertNotEmpty($apres['libelles']['multi_quota']['fr'] ?? null);
        $this->assertGreaterThan(0, (int) ($apres['essai_jours'] ?? 0));
    }

    public function test_les_libelles_des_fonctionnalites_sont_servis_et_editables(): void
    {
        $r = $this->getJson('/api/vitrine/tarification')->assertOk();

        // Sans ces libellés, les deux écrans retomberaient sur les textes figés.
        $r->assertJsonStructure(['libelles' => ['multi_quota' => ['fr', 'en']]]);
        $this->assertStringContainsString('{n}', $r->json('libelles.multi_quota.fr'));
    }
}
