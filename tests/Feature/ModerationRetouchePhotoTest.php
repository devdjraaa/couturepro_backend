<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\RealisationController;
use App\Models\Realisation;
use App\Services\WatermarkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
}
