<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\RealisationController;
use App\Models\Admin;
use App\Models\Atelier;
use App\Models\Proprietaire;
use App\Models\Realisation;
use App\Services\WatermarkService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * PHOTO-3/7 — filtrage du chemin servi au modérateur.
 *
 * `GET /admin/realisations/{id}/fichier?path=…` prend un chemin en paramètre.
 * Sans confrontation aux images de CETTE réalisation, il servirait n'importe
 * quel fichier du disque à qui possède un compte admin. C'est la garantie la
 * plus sensible de la fonctionnalité, et elle ne dépend d'aucune base : le test
 * instancie le modèle sans l'enregistrer.
 */
class ModerationRetouchePhotoTest extends TestCase
{
    use RefreshDatabase;

    private function controleur(): RealisationController
    {
        return new RealisationController(app(WatermarkService::class));
    }

    private function realisation(array $images): Realisation
    {
        $r = new Realisation();
        $r->images = $images;

        return $r;
    }

    private function requete(string $path): Request
    {
        return Request::create('/', 'GET', ['path' => $path]);
    }

    public function test_un_chemin_hors_realisation_est_refuse(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('ailleurs/prive.jpg', 'contenu');

        $realisation = $this->realisation([
            ['path' => 'realisations/a/photo.jpg', 'url' => 'http://exemple/photo.jpg'],
        ]);

        $reponse = $this->controleur()->fichier($this->requete('ailleurs/prive.jpg'), $realisation);

        $this->assertSame(404, $reponse->getStatusCode(),
            'un fichier étranger à la réalisation ne doit jamais être servi');
    }

    public function test_l_original_est_servi(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('realisations/a/photo.jpg', 'image');

        $realisation = $this->realisation([
            ['path' => 'realisations/a/photo.jpg', 'url' => 'http://exemple/photo.jpg'],
        ]);

        $reponse = $this->controleur()->fichier($this->requete('realisations/a/photo.jpg'), $realisation);

        $this->assertSame(200, $reponse->getStatusCode());
    }

    public function test_la_retouche_reste_accessible_et_l_original_aussi(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('realisations/a/photo.jpg', 'original');
        Storage::disk('public')->put('realisations/a/retouches/photo.jpg', 'retouche');

        // Traçabilité (exigence direction) : après retouche, les DEUX versions
        // doivent rester consultables par le modérateur.
        $realisation = $this->realisation([[
            'path'          => 'realisations/a/photo.jpg',
            'url'           => 'http://exemple/photo.jpg',
            'retouche_path' => 'realisations/a/retouches/photo.jpg',
            'retouche_url'  => 'http://exemple/retouche.jpg',
        ]]);

        $this->assertSame(200, $this->controleur()
            ->fichier($this->requete('realisations/a/photo.jpg'), $realisation)->getStatusCode());

        $this->assertSame(200, $this->controleur()
            ->fichier($this->requete('realisations/a/retouches/photo.jpg'), $realisation)->getStatusCode());
    }

    public function test_un_fichier_reference_mais_absent_du_disque_renvoie_404(): void
    {
        Storage::fake('public');

        $realisation = $this->realisation([
            ['path' => 'realisations/a/disparue.jpg', 'url' => 'http://exemple/x.jpg'],
        ]);

        $reponse = $this->controleur()->fichier($this->requete('realisations/a/disparue.jpg'), $realisation);

        $this->assertSame(404, $reponse->getStatusCode());
    }

    // ── Parcours complet, avec base ──────────────────────────────────────

    private function realisationEnregistree(): Realisation
    {
        $proprietaire = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local',
            'nom' => 'Test', 'prenom' => 'Moderation',
            'question_secrete' => 'q', 'reponse_secrete' => 'r',
            'password' => bcrypt('motdepasse'),
        ]);

        $atelier = Atelier::create([
            'proprietaire_id' => $proprietaire->id,
            'nom' => 'Atelier de test',
        ]);

        $chemin = UploadedFile::fake()->image('photo.jpg', 800, 600)
            ->store('realisations/' . $atelier->id, 'public');

        return Realisation::create([
            'atelier_id' => $atelier->id,
            'titre'      => 'Test modération',
            'statut'     => Realisation::STATUT_EN_ATTENTE,
            'soumis_at'  => now(),
            'images'     => [['path' => $chemin, 'url' => 'http://exemple/' . $chemin]],
        ]);
    }

    private function admin(): Admin
    {
        // `super_admin` : les routes de modération sont derrière
        // `admin.permission:realisations.moderate`, et le rôle `admin` par
        // défaut ne la porte pas.
        return Admin::create([
            'nom' => 'Admin', 'prenom' => 'Test',
            'email' => Str::uuid() . '@test.local',
            'password' => bcrypt('motdepasse'),
            'role' => 'super_admin',
            'is_active' => true,   // AdminAuth refuse un compte inactif
        ]);
    }

    public function test_la_retouche_ne_detruit_jamais_l_original(): void
    {
        Storage::fake('public');
        $realisation = $this->realisationEnregistree();
        $original = $realisation->images[0]['path'];

        $this->actingAs($this->admin(), 'admin')
            ->post("/api/admin/realisations/{$realisation->id}/retoucher", [
                'path'  => $original,
                'photo' => UploadedFile::fake()->image('retouche.jpg', 400, 300),
            ])
            ->assertOk();

        $image = $realisation->fresh()->images[0];

        $this->assertNotEmpty($image['retouche_path'] ?? null, 'la retouche doit être enregistrée');
        $this->assertNotSame($original, $image['retouche_path']);
        // L'exigence de la direction : droits d'auteur et traçabilité.
        $this->assertTrue(Storage::disk('public')->exists($original), "l'original doit survivre à la retouche");
    }

    public function test_une_realisation_deja_publiee_n_est_plus_retouchable(): void
    {
        Storage::fake('public');
        $realisation = $this->realisationEnregistree();
        $realisation->update(['statut' => Realisation::STATUT_PUBLIEE]);

        $this->actingAs($this->admin(), 'admin')
            ->postJson("/api/admin/realisations/{$realisation->id}/retoucher", [
                'path'  => $realisation->images[0]['path'],
                'photo' => UploadedFile::fake()->image('retouche.jpg'),
            ])
            ->assertStatus(422);
    }

    public function test_la_file_expose_l_echeance_de_24h(): void
    {
        Storage::fake('public');
        $this->realisationEnregistree();

        $reponse = $this->actingAs($this->admin(), 'admin')
            ->getJson('/api/admin/realisations?statut=en_attente')
            ->assertOk();

        // L'écran s'appuie sur ces deux champs pour le décompte : l'horloge du
        // poste du modérateur ne doit pas faire autorité sur l'échéance.
        $premier = $reponse->json('data.0');
        $this->assertArrayHasKey('limite_moderation', $premier);
        $this->assertArrayHasKey('en_retard', $premier);
        $this->assertFalse($premier['en_retard'], 'une réalisation soumise à l\'instant n\'est pas en retard');
    }
}
