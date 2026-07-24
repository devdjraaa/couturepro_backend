<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\Proprietaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Préférences de l'atelier (devise + unité de mesure).
 *
 * La devise pilote l'affichage des montants partout (commandes, factures,
 * caisse) : une valeur hors liste fausserait la facturation. Ces tests
 * verrouillent l'aller-retour et le refus d'une devise inconnue.
 */
class ParametresPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function proprietaire(): Proprietaire
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local', 'nom' => 'Houngbo', 'prenom' => 'Sena',
            'question_secrete' => 'q', 'reponse_secrete' => 'r', 'password' => bcrypt('x'),
        ]);
        $a = Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Atelier Sena',
            'type' => 'artisan', 'is_maitre' => true, 'statut' => 'actif',
        ]);
        Abonnement::create([
            'atelier_id' => $a->id, 'niveau_cle' => 'atelier_mensuel', 'statut' => 'actif',
            'jours_restants' => 30, 'timestamp_debut' => now(), 'timestamp_expiration' => now()->addDays(30),
            'config_snapshot' => [],
        ]);

        return $p;
    }

    public function test_valeurs_par_defaut(): void
    {
        $this->actingAs($this->proprietaire(), 'sanctum')
             ->getJson('/api/parametres/preferences')
             ->assertOk()
             ->assertJsonPath('devise', 'XOF')
             ->assertJsonPath('unite_mesure', 'cm');
    }

    public function test_aller_retour(): void
    {
        $p = $this->proprietaire();
        $act = $this->actingAs($p, 'sanctum');

        $act->putJson('/api/parametres/preferences', ['devise' => 'EUR', 'unite_mesure' => 'pouces'])
            ->assertOk();

        $act->getJson('/api/parametres/preferences')
            ->assertOk()
            ->assertJsonPath('devise', 'EUR')
            ->assertJsonPath('unite_mesure', 'pouces');
    }

    public function test_une_devise_inconnue_est_refusee(): void
    {
        $this->actingAs($this->proprietaire(), 'sanctum')
             ->putJson('/api/parametres/preferences', ['devise' => 'BTC', 'unite_mesure' => 'cm'])
             ->assertStatus(422);
    }
}
