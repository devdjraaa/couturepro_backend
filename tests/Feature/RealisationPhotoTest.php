<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\Proprietaire;
use App\Models\Realisation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * « Mes Réalisations » — enregistrement des photos.
 *
 * La direction remontait « impossible d'enregistrer les photos ». Le flux est
 * volontairement en DEUX temps : on crée le brouillon (sans photo), puis on
 * ajoute chaque photo par POST /photo, où passe le contrôle qualité automatique.
 * Ces tests verrouillent ce contrat : une vraie photo est acceptée et persistée,
 * une image hors critères est refusée avec le code `qualite` (que l'écran
 * traduit en icônes) — jamais un échec muet.
 */
class RealisationPhotoTest extends TestCase
{
    use RefreshDatabase;

    private function proprietaireAvecAtelier(): Proprietaire
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local', 'nom' => 'Kora', 'prenom' => 'Ife',
            'question_secrete' => 'q', 'reponse_secrete' => 'r', 'password' => bcrypt('x'),
        ]);
        $a = Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Studio Ife',
            'type' => 'designer', 'is_maitre' => true, 'statut' => 'actif',
        ]);
        Abonnement::create([
            'atelier_id' => $a->id, 'niveau_cle' => 'master_mensuel', 'statut' => 'actif',
            'jours_restants' => 30, 'timestamp_debut' => now(), 'timestamp_expiration' => now()->addDays(30),
            'config_snapshot' => ['max_photos_realisation' => 6],
        ]);

        return $p;
    }

    /** Une photo réaliste (assez grande, ni trop sombre ni trop claire, texturée). */
    private function photoValide(): UploadedFile
    {
        $l = 900;
        $h = 1000;
        $img = imagecreatetruecolor($l, $h);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $l; $x++) {
                // Gris moyen (~128) + bruit : luminosité dans la bande, variance
                // du laplacien largement au-dessus du plancher de netteté.
                $n = random_int(-60, 60);
                $v = max(0, min(255, 128 + $n));
                imagesetpixel($img, $x, $y, imagecolorallocate($img, $v, $v, $v));
            }
        }
        $chemin = tempnam(sys_get_temp_dir(), 'rea') . '.jpg';
        imagejpeg($img, $chemin, 92);
        imagedestroy($img);

        return new UploadedFile($chemin, 'photo.jpg', 'image/jpeg', null, true);
    }

    public function test_creer_puis_ajouter_une_vraie_photo_la_persiste(): void
    {
        Storage::fake('public');
        $p = $this->proprietaireAvecAtelier();
        $act = $this->actingAs($p, 'sanctum');

        $id = $act->postJson('/api/realisations', ['titre' => 'Robe wax'])
                  ->assertCreated()->json('realisation.id');

        $r = $act->post("/api/realisations/{$id}/photo", ['photo' => $this->photoValide()]);
        $r->assertCreated();

        $this->assertCount(1, Realisation::find($id)->images);
    }

    public function test_une_image_hors_criteres_est_refusee_avec_le_code_qualite(): void
    {
        Storage::fake('public');
        $p = $this->proprietaireAvecAtelier();
        $act = $this->actingAs($p, 'sanctum');

        $id = $act->postJson('/api/realisations', ['titre' => 'Essai'])
                  ->assertCreated()->json('realisation.id');

        // 50x50 : sous le plancher de résolution → refus qualité, pas un échec muet.
        $trop_petite = UploadedFile::fake()->image('mini.jpg', 50, 50);
        $act->post("/api/realisations/{$id}/photo", ['photo' => $trop_petite])
            ->assertStatus(422)
            ->assertJsonPath('code', 'qualite');

        $this->assertEmpty(Realisation::find($id)->images ?? []);
    }
}
