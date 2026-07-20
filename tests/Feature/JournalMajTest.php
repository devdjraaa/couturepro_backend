<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\VitrineSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CLI-1 — Journal des mises à jour (« Quoi de neuf »).
 *
 * Le point qui mérite un test est le TRI : la liste est rendue de la plus
 * récente à la plus ancienne quel que soit l'ordre de saisie en admin. Sans
 * cela, une entrée ajoutée en bas du formulaire apparaîtrait en tête chez les
 * utilisateurs, et la pastille « du nouveau » — qui regarde la PREMIÈRE
 * entrée — se déclencherait sur une vieille version.
 */
class JournalMajTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): Admin
    {
        return Admin::create([
            'nom' => 'Admin', 'prenom' => 'Maj',
            'email' => Str::uuid() . '@test.local',
            'password' => bcrypt('motdepasse'),
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function entree(string $version, string $date): array
    {
        return [
            'version' => $version, 'date' => $date,
            'titre' => "Version $version", 'type' => 'nouveaute',
            'lignes' => ['Un changement'],
        ];
    }

    public function test_le_journal_est_rendu_du_plus_recent_au_plus_ancien(): void
    {
        // Saisi dans le désordre, comme cela arrivera en admin.
        VitrineSetting::create(['cle' => 'journal_maj', 'valeur' => [
            $this->entree('1.0.90', '2026-07-10'),
            $this->entree('1.0.97', '2026-07-20'),
            $this->entree('1.0.94', '2026-07-15'),
        ]]);

        $versions = array_column(VitrineSetting::journalMaj(), 'version');

        $this->assertSame(['1.0.97', '1.0.94', '1.0.90'], $versions);
    }

    public function test_le_journal_est_public(): void
    {
        VitrineSetting::create(['cle' => 'journal_maj', 'valeur' => [$this->entree('1.0.97', '2026-07-20')]]);

        // Sans jeton : l'écran doit rester lisible quand la session a expiré,
        // c'est même le moment où l'on cherche à comprendre ce qui a changé.
        $this->getJson('/api/app/journal-maj')
            ->assertOk()
            ->assertJsonPath('entrees.0.version', '1.0.97');
    }

    public function test_un_journal_vide_rend_une_liste_vide(): void
    {
        $this->getJson('/api/app/journal-maj')
            ->assertOk()
            ->assertJsonPath('entrees', []);
    }

    public function test_un_type_inconnu_est_refuse(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->putJson('/api/admin/vitrine/journal-maj', ['entrees' => [
                ['version' => '1.0', 'date' => '2026-07-20', 'titre' => 'X', 'type' => 'fantaisie', 'lignes' => []],
            ]])
            ->assertStatus(422);
    }

    public function test_une_date_mal_formee_est_refusee(): void
    {
        // La date porte le tri : une valeur libre casserait l'ordre d'affichage.
        $this->actingAs($this->admin(), 'admin')
            ->putJson('/api/admin/vitrine/journal-maj', ['entrees' => [
                ['version' => '1.0', 'date' => '20/07/2026', 'titre' => 'X', 'type' => 'nouveaute', 'lignes' => []],
            ]])
            ->assertStatus(422);
    }

    public function test_l_admin_enregistre_et_relit(): void
    {
        $this->actingAs($this->admin(), 'admin')
            ->putJson('/api/admin/vitrine/journal-maj', ['entrees' => [
                $this->entree('1.0.97', '2026-07-20'),
            ]])
            ->assertOk()
            ->assertJsonPath('entrees.0.titre', 'Version 1.0.97');

        $this->getJson('/api/app/journal-maj')->assertJsonPath('entrees.0.version', '1.0.97');
    }
}
