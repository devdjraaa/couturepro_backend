<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Atelier;
use App\Models\CreationLike;
use App\Models\Proprietaire;
use App\Models\Vetement;
use App\Models\VitrineSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Galerie publique : catégories éditables, vues dédupliquées, likes, et
 * classement du contenu mis en avant.
 *
 * Ces comportements n'existaient pas avant le 24/07 : la galerie renvoyait
 * « categorie => null » en dur, aucune vue, et le sponsoring ne touchait que le
 * classement des créateurs, jamais la galerie de créations.
 */
class GalerieCreationsTest extends TestCase
{
    use RefreshDatabase;

    private function designer(bool $sponsorise = false): Atelier
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local',
            'nom' => 'Zinsou', 'prenom' => 'Ama',
            'question_secrete' => 'q', 'reponse_secrete' => 'r',
            'password' => bcrypt('motdepasse'),
        ]);

        return Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Maison ' . Str::random(4),
            'type' => 'designer', 'is_demo' => false, 'is_maitre' => true, 'statut' => 'actif',
            'sponsor_jusqu_a' => $sponsorise ? now()->addDays(7) : null,
        ]);
    }

    private function creation(Atelier $a, array $attrs = []): Vetement
    {
        return Vetement::create(array_merge([
            'atelier_id' => $a->id, 'nom' => 'Création ' . Str::random(4),
            'publie_vitrine' => true, 'is_archived' => false, 'is_systeme' => false,
        ], $attrs));
    }

    public function test_les_categories_actives_sont_servies(): void
    {
        $r = $this->getJson('/api/vitrine/categories-creations')->assertOk();

        $this->assertNotEmpty($r->json());
        $this->assertArrayHasKey('cle', $r->json()[0]);
        $this->assertArrayHasKey('fr', $r->json()[0]['label']);
    }

    public function test_une_categorie_desactivee_disparait_des_filtres(): void
    {
        VitrineSetting::updateOrCreate(['cle' => 'categories_creations'], ['valeur' => [
            ['cle' => 'robes', 'actif' => true,  'label' => ['fr' => 'Robes', 'en' => 'Dresses']],
            ['cle' => 'cachee', 'actif' => false, 'label' => ['fr' => 'Cachée', 'en' => 'Hidden']],
        ]]);

        $cles = collect($this->getJson('/api/vitrine/categories-creations')->json())->pluck('cle');
        $this->assertContains('robes', $cles);
        $this->assertNotContains('cachee', $cles);
    }

    public function test_la_galerie_expose_categorie_vues_et_likes(): void
    {
        $a = $this->designer();
        $v = $this->creation($a, ['categorie' => 'robes', 'vues' => 12]);
        CreationLike::create(['vetement_id' => $v->id, 'visitor_key' => 'k1']);

        $item = collect($this->getJson('/api/vitrine/creations')->assertOk()->json())
            ->firstWhere('id', (string) $v->id);

        $this->assertSame('robes', $item['categorie']);
        $this->assertSame(12, $item['vues']);
        $this->assertSame(1, $item['likes']);
    }

    public function test_le_filtre_par_categorie_fonctionne(): void
    {
        $a = $this->designer();
        $this->creation($a, ['categorie' => 'robes']);
        $this->creation($a, ['categorie' => 'enfant']);

        $cles = collect($this->getJson('/api/vitrine/creations?categorie=robes')->json())->pluck('categorie')->unique();
        $this->assertEquals(['robes'], $cles->values()->all());
    }

    public function test_le_contenu_sponsorise_remonte_en_tete_avec_son_drapeau(): void
    {
        $ordinaire = $this->designer(sponsorise: false);
        $sponso    = $this->designer(sponsorise: true);
        // L'ordinaire est créé APRÈS pour qu'un simple tri chronologique le
        // placerait devant : seul le classement sponsorisé le fait passer second.
        $this->creation($sponso);
        $this->creation($ordinaire);

        $liste = $this->getJson('/api/vitrine/creations')->assertOk()->json();

        $this->assertTrue($liste[0]['sponsorise'], 'Le contenu mis en avant doit venir en premier.');
        $this->assertFalse($liste[1]['sponsorise']);
    }

    public function test_une_vue_est_comptee_une_fois_puis_dedupliquee(): void
    {
        Cache::flush();
        $v = $this->creation($this->designer());

        $this->postJson("/api/vitrine/creations/{$v->id}/vue", ['visitor_key' => 'visiteur-1'])
             ->assertOk()->assertJson(['vues' => 1]);

        // Deuxième vue du MÊME visiteur dans la fenêtre : pas de nouvel incrément.
        $this->postJson("/api/vitrine/creations/{$v->id}/vue", ['visitor_key' => 'visiteur-1'])
             ->assertOk()->assertJson(['vues' => 1]);

        // Un autre visiteur compte.
        $this->postJson("/api/vitrine/creations/{$v->id}/vue", ['visitor_key' => 'visiteur-2'])
             ->assertOk()->assertJson(['vues' => 2]);
    }

    public function test_le_sitemap_priorise_les_ateliers_sponsorises(): void
    {
        $ordinaire = $this->designer(sponsorise: false);
        $sponso    = $this->designer(sponsorise: true);

        $xml = $this->get('/api/sitemap/createurs.xml')->assertOk()->getContent();

        // Le sponsorisé est signalé prioritaire et à rafraîchir chaque jour ;
        // l'ordinaire garde la priorité de base et une fréquence hebdomadaire.
        // Le <lastmod> optionnel peut s'intercaler entre </loc> et <changefreq>.
        $this->assertMatchesRegularExpression(
            "#/createurs/{$sponso->id}</loc>(?:<lastmod>[^<]*</lastmod>)?<changefreq>daily</changefreq><priority>1\.0</priority>#",
            $xml,
        );
        $this->assertMatchesRegularExpression(
            "#/createurs/{$ordinaire->id}</loc>(?:<lastmod>[^<]*</lastmod>)?<changefreq>weekly</changefreq><priority>0\.7</priority>#",
            $xml,
        );
    }

    public function test_l_admin_edite_la_taxonomie_et_refuse_les_cles_doublons(): void
    {
        $admin = Admin::create([
            'nom' => 'Admin', 'prenom' => 'Cat', 'email' => Str::uuid() . '@test.local',
            'password' => bcrypt('motdepasse'), 'role' => 'super_admin', 'is_active' => true,
        ]);

        // Doublon de clé : refusé.
        $this->actingAs($admin, 'admin')
             ->putJson('/api/admin/vitrine/categories-creations', ['categories' => [
                 ['cle' => 'robes', 'actif' => true, 'label' => ['fr' => 'Robes']],
                 ['cle' => 'robes', 'actif' => true, 'label' => ['fr' => 'Encore']],
             ]])->assertStatus(422);

        // Taxonomie valide : acceptée et relue.
        $this->actingAs($admin, 'admin')
             ->putJson('/api/admin/vitrine/categories-creations', ['categories' => [
                 ['cle' => 'capes', 'actif' => true, 'label' => ['fr' => 'Capes', 'en' => 'Capes']],
             ]])->assertOk();

        $this->assertEquals(['capes'], collect(VitrineSetting::categoriesCreations())->pluck('cle')->all());
    }
}
