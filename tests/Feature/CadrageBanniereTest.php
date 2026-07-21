<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\Proprietaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * VIT-3 — Cadrage de la bannière.
 *
 * Le cadrage est stocké en FRACTIONS de l'image (0 → 1), jamais en pixels : la
 * même bannière est servie à plusieurs tailles et des pixels deviendraient faux
 * dès la première miniature.
 *
 * Le contrôle qui compte est qu'un cadre ne puisse pas DÉBORDER : un cadre qui
 * dépasse afficherait des bandes vides sur le profil public d'un créateur.
 */
class CadrageBanniereTest extends TestCase
{
    use RefreshDatabase;

    private function atelier(): Atelier
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local',
            'nom' => 'Test', 'prenom' => 'Cadrage',
            'question_secrete' => 'q', 'reponse_secrete' => 'r',
            'password' => bcrypt('motdepasse'),
        ]);

        return Atelier::create([
            'proprietaire_id' => $p->id,
            'nom' => 'Atelier cadrage',
            'is_maitre' => true,
            'banniere_path' => 'ateliers/x/banniere/photo.jpg',
            'banniere_type' => 'image',
        ]);
    }

    public function test_un_cadrage_valide_est_enregistre_puis_relu(): void
    {
        $atelier = $this->atelier();

        $this->actingAs($atelier->proprietaire, 'sanctum')
            ->putJson('/api/parametres/atelier/banniere/cadrage', [
                'x' => 0.1, 'y' => 0.2, 'largeur' => 0.6, 'hauteur' => 0.5,
            ])
            ->assertOk()
            ->assertJsonPath('banniere_cadrage.largeur', 0.6);

        $this->assertSame(0.1, $atelier->fresh()->banniere_cadrage['x']);
    }

    public function test_un_cadre_qui_deborde_est_refuse(): void
    {
        $atelier = $this->atelier();

        // 0.7 + 0.5 = 1.2 : le cadre sort de l'image. Accepté, il afficherait
        // des bandes vides sur le profil public.
        $this->actingAs($atelier->proprietaire, 'sanctum')
            ->putJson('/api/parametres/atelier/banniere/cadrage', [
                'x' => 0.7, 'y' => 0, 'largeur' => 0.5, 'hauteur' => 0.5,
            ])
            ->assertStatus(422);
    }

    public function test_un_cadre_minuscule_est_refuse(): void
    {
        $atelier = $this->atelier();

        // Recadrer un timbre-poste rendrait la bannière publiée inexploitable.
        $this->actingAs($atelier->proprietaire, 'sanctum')
            ->putJson('/api/parametres/atelier/banniere/cadrage', [
                'x' => 0, 'y' => 0, 'largeur' => 0.01, 'hauteur' => 0.01,
            ])
            ->assertStatus(422);
    }

    public function test_cadrer_sans_banniere_echoue(): void
    {
        $atelier = $this->atelier();
        $atelier->update(['banniere_path' => null]);

        $this->actingAs($atelier->proprietaire, 'sanctum')
            ->putJson('/api/parametres/atelier/banniere/cadrage', [
                'x' => 0, 'y' => 0, 'largeur' => 1, 'hauteur' => 1,
            ])
            ->assertStatus(422);
    }
}
