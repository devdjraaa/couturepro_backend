<?php

namespace Tests\Feature;

use App\Console\Commands\VeilleRenduRobots;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * La surveillance doit DETECTER la panne, pas seulement tourner.
 *
 * Une surveillance qu'on n'a jamais vue echouer ne prouve rien : c'est
 * exactement le piege paye ce matin sur le cache, ou le premier test passait
 * sur le code casse. Chaque controle est donc verifie dans les DEUX sens.
 */
class VeilleRenduRobotsTest extends TestCase
{
    private function page(string $titre, string $description = 'Une description.'): string
    {
        return "<html><head><title>{$titre}</title>"
             . "<meta name=\"description\" content=\"{$description}\">"
             . '</head><body>'.str_repeat('contenu ', 60).'</body></html>';
    }

    /** Le cas sain : le robot recoit autre chose que le navigateur. */
    public function test_aucune_anomalie_quand_le_prerendu_sapplique(): void
    {
        Http::fake(function ($request) {
            $robot = str_contains($request->header('User-Agent')[0] ?? '', 'Googlebot');

            return Http::response($this->page(
                $robot ? 'Createurs et ateliers de mode africaine | Gextimo' : 'Gextimo — La marketplace',
            ));
        });

        $this->artisan('veille:rendu-robots', [
            '--url' => ['https://exemple.test/createurs'],
            '--silencieux' => true,
        ])->assertSuccessful();
    }

    /**
     * LE controle central.
     *
     * Deux titres identiques sur une page autre que l'accueil signifient que le
     * robot a recu la coquille de l'application : le routage est inerte,
     * exactement comme entre le 20 et le 23/07.
     */
    public function test_detecte_un_prerendu_redevenu_inerte(): void
    {
        Http::fake(fn () => Http::response($this->page('Gextimo — La marketplace')));

        $this->artisan('veille:rendu-robots', [
            '--url' => ['https://exemple.test/createurs'],
            '--silencieux' => true,
        ])->assertFailed();
    }

    /**
     * Sur l'accueil, deux titres identiques sont NORMAUX.
     *
     * Sans cette exception, la surveillance crierait a chaque passage — et une
     * alerte qui se declenche a tort finit par etre ignoree, y compris le jour
     * ou elle a raison.
     */
    public function test_pas_de_fausse_alerte_sur_la_page_daccueil(): void
    {
        Http::fake(fn () => Http::response($this->page('Gextimo — La marketplace')));

        $this->artisan('veille:rendu-robots', [
            '--url' => ['https://exemple.test/'],
            '--silencieux' => true,
        ])->assertSuccessful();
    }

    public function test_detecte_une_reponse_sans_titre(): void
    {
        Http::fake(fn () => Http::response('<html><head></head><body>rien</body></html>'));

        $this->artisan('veille:rendu-robots', [
            '--url' => ['https://exemple.test/createurs'],
            '--silencieux' => true,
        ])->assertFailed();
    }

    public function test_detecte_une_page_injoignable(): void
    {
        Http::fake(fn () => Http::response('', 503));

        $this->artisan('veille:rendu-robots', [
            '--url' => ['https://exemple.test/createurs'],
            '--silencieux' => true,
        ])->assertFailed();
    }

    /**
     * Chaque passage laisse une trace, meme en anomalie.
     *
     * C'est ce qui permet au chien de garde du chien de garde de distinguer
     * « la surveillance a trouve un probleme » de « la surveillance ne tourne
     * plus » — la seconde etant la panne la plus sournoise.
     */
    public function test_chaque_passage_laisse_une_trace(): void
    {
        Cache::forget(VeilleRenduRobots::CLE_DERNIER_PASSAGE);
        Http::fake(fn () => Http::response($this->page('Gextimo — La marketplace')));

        $this->artisan('veille:rendu-robots', [
            '--url' => ['https://exemple.test/createurs'],
            '--silencieux' => true,
        ])->assertFailed();

        $this->assertNotNull(
            Cache::get(VeilleRenduRobots::CLE_DERNIER_PASSAGE),
            'une execution en anomalie doit quand meme prouver que la surveillance tourne',
        );
    }
}
