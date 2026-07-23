<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\Client;
use App\Models\Mesure;
use App\Models\Proprietaire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Export CSV des mesures d'un client.
 *
 * Le bouton cote front etait casse de deux facons, invisibles au build :
 *   - il pointait vers un `<a href download>`, qui n'envoie PAS le jeton — la
 *     route etant protegee, le telechargement aurait recu un 401 ;
 *   - l'URL etait construite par une methode `async`, donc `href` valait
 *     `[object Promise]`.
 *
 * Ces tests verrouillent le contrat serveur sur lequel le correctif s'appuie :
 * 401 sans jeton, CSV correct avec, et un atelier ne lit pas les mesures d'un
 * autre.
 */
class ExportMesuresCsvTest extends TestCase
{
    use RefreshDatabase;

    private function atelierAvecClient(array $champs = ['tour_poitrine' => '92', 'tour_taille' => '74']): array
    {
        $proprietaire = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email'     => Str::uuid() . '@test.local',
            'nom'       => 'Sossou', 'prenom' => 'Awa',
            'question_secrete' => 'q', 'reponse_secrete' => 'r',
            'password'  => bcrypt('motdepasse'),
        ]);

        $atelier = Atelier::create([
            'proprietaire_id' => $proprietaire->id,
            'nom'             => 'Atelier Awa',
            'is_maitre'       => true,
        ]);

        $client = Client::create([
            'atelier_id' => $atelier->id,
            'created_by'      => $proprietaire->id,
            'created_by_role' => 'proprietaire',
            'prenom'     => 'Fatou',
            'nom'        => 'Diallo',
        ]);

        if ($champs !== []) {
            Mesure::create([
                'atelier_id'      => $atelier->id,
                'client_id'       => $client->id,
                'champs'          => $champs,
                'created_by'      => $proprietaire->id,
                'created_by_role' => 'proprietaire',
            ]);
        }

        return [$atelier, $client];
    }

    public function test_sans_jeton_la_route_repond_401(): void
    {
        [, $client] = $this->atelierAvecClient();

        $this->getJson("/api/clients/{$client->id}/mesures/export-csv")
             ->assertUnauthorized();
    }

    public function test_le_proprietaire_telecharge_un_csv_correct(): void
    {
        [$atelier, $client] = $this->atelierAvecClient();

        $r = $this->actingAs($atelier->proprietaire, 'sanctum')
                  ->get("/api/clients/{$client->id}/mesures/export-csv");

        $r->assertOk();
        $r->assertHeader('content-type', 'text/csv; charset=UTF-8');
        // Fichier telechargeable, nomme d'apres le client.
        $this->assertStringContainsString('attachment', $r->headers->get('content-disposition'));
        $this->assertStringContainsString('fatou_diallo', $r->headers->get('content-disposition'));

        $corps = $r->streamedContent();
        // BOM UTF-8 en tete : sans lui, Excel casse les accents.
        $this->assertStringStartsWith("\xEF\xBB\xBF", $corps);
        $this->assertStringContainsString('Tour poitrine', $corps);
        $this->assertStringContainsString('92', $corps);
        $this->assertStringContainsString($atelier->nom, $corps);
    }

    public function test_un_atelier_ne_telecharge_pas_les_mesures_d_un_autre(): void
    {
        [, $client] = $this->atelierAvecClient();
        [$autreAtelier] = $this->atelierAvecClient();

        // Le proprietaire de l'AUTRE atelier ne doit pas atteindre ce client.
        $this->actingAs($autreAtelier->proprietaire, 'sanctum')
             ->get("/api/clients/{$client->id}/mesures/export-csv")
             ->assertNotFound();
    }

    public function test_un_client_sans_mesure_donne_un_csv_a_entetes_seules(): void
    {
        [$atelier, $client] = $this->atelierAvecClient(champs: []);

        $r = $this->actingAs($atelier->proprietaire, 'sanctum')
                  ->get("/api/clients/{$client->id}/mesures/export-csv")
                  ->assertOk();

        // L'en-tete est la, aucune ligne de mesure : un fichier vide vaut mieux
        // qu'une erreur.
        $this->assertStringContainsString('Mesure', $r->streamedContent());
    }
}
