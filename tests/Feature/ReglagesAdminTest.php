<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\VitrineSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Réglages que la direction doit pouvoir piloter elle-même.
 *
 * Quatre d'entre eux n'avaient qu'une route d'ÉCRITURE : on pouvait les
 * écraser à l'aveugle, jamais les relire, donc aucun écran ne pouvait les
 * présenter. Le cas le plus gênant était les PALIERS DE FIDÉLITÉ — la
 * direction devait recalibrer un programme mathématiquement inatteignable sans
 * avoir aucun moyen de le faire autrement qu'en passant par un développeur.
 *
 * Ces tests garantissent l'aller-retour complet : ce qu'on enregistre est ce
 * qu'on relit.
 */
class ReglagesAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'nom' => 'Admin', 'prenom' => 'Reglages',
            'email' => Str::uuid() . '@test.local',
            'password' => bcrypt('motdepasse'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    public function test_les_paliers_de_fidelite_se_relisent_apres_ecriture(): void
    {
        $paliers = [
            ['cle' => 'bronze', 'nom' => 'Bronze', 'seuil' => 0],
            ['cle' => 'argent', 'nom' => 'Argent', 'seuil' => 200],
        ];

        $this->actingAs($this->admin(), 'admin')
            ->putJson('/api/admin/vitrine/paliers-fidelite', ['paliers' => $paliers])
            ->assertOk();

        $this->actingAs($this->admin(), 'admin')
            ->getJson('/api/admin/vitrine/paliers-fidelite')
            ->assertOk()
            ->assertJsonPath('paliers.1.seuil', 200);
    }

    public function test_les_coordonnees_se_relisent_apres_ecriture(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->putJson('/api/admin/vitrine/coordonnees', [
                'marque' => 'Gextimo', 'site' => 'gextimo.novafriq.africa', 'telephone' => '+229 00 00 00 00',
            ])->assertOk();

        $this->actingAs($this->admin(), 'admin')
            ->getJson('/api/admin/vitrine/coordonnees')
            ->assertOk()
            ->assertJsonPath('telephone', '+229 00 00 00 00');
    }

    public function test_les_moyens_de_paiement_se_relisent_apres_ecriture(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->putJson('/api/admin/vitrine/moyens-paiement', ['moyens' => [
                ['cle' => 'fedapay', 'label' => 'FedaPay', 'actif' => true, 'defaut' => true],
                ['cle' => 'virement', 'label' => 'Virement', 'actif' => false, 'defaut' => false],
            ]])->assertOk();

        $this->actingAs($this->admin(), 'admin')
            ->getJson('/api/admin/vitrine/moyens-paiement')
            ->assertOk()
            ->assertJsonCount(2, 'moyens');
    }

    public function test_le_mot_de_passe_vasat_n_est_jamais_renvoye(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->putJson('/api/admin/vitrine/vasat', ['mdp' => 'motdepassesolide', 'actif' => true])
            ->assertOk();

        $reponse = $this->actingAs($this->admin(), 'admin')
            ->getJson('/api/admin/vitrine/vasat')
            ->assertOk()
            ->assertJsonPath('actif', true)
            ->assertJsonPath('defini', true);

        // Ni le mot de passe ni son hachage : un hachage exposé se casse hors
        // ligne, et le rendre visible à l'écran n'apporte rien.
        $brut = $reponse->getContent();
        $this->assertStringNotContainsString('motdepassesolide', $brut);
        $this->assertStringNotContainsString('mdp_hash', $brut);
        $this->assertStringNotContainsString('$2y$', $brut);
    }

    public function test_un_visiteur_ne_peut_pas_lire_ces_reglages(): void
    {
        VitrineSetting::create(['cle' => 'coordonnees', 'valeur' => ['telephone' => 'secret']]);

        foreach (['paliers-fidelite', 'coordonnees', 'moyens-paiement', 'vasat'] as $cle) {
            $this->getJson("/api/admin/vitrine/{$cle}")->assertUnauthorized();
        }
    }
}
