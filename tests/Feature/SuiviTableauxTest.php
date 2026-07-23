<?php
namespace Tests\Feature;
use Tests\TestCase;

/**
 * Chaque tableau de suivi doit ecrire dans SON fichier.
 *
 * La sauvegarde remplace la table `checked` en entier. Comme les identifiants
 * de la v2 (s60/s70/s80) et de la v3 (s90) ne se recouvrent pas, un stockage
 * commun aurait fait revenir l'ancienne page TOUTE DECOCHEE des la premiere
 * sauvegarde de la nouvelle — sans erreur, sans que personne ne le voie.
 */
class SuiviTableauxTest extends TestCase
{
    public function test_v2_et_v3_ne_se_marchent_pas_dessus(): void
    {
        @unlink(storage_path('app/suivi-v3.json'));
        $v2 = storage_path('app/suivi-sprints.json');
        $avant = is_file($v2) ? file_get_contents($v2) : null;

        // On pose un état sur le tableau v3 avec son propre code.
        $r = $this->postJson('/api/suivi-sprints?tableau=v3', [
            'code' => '424242', 'checked' => ['s90t1' => true, 's90t7' => true],
        ]);
        $r->assertOk();
        $this->assertTrue(is_file(storage_path('app/suivi-v3.json')), 'le fichier v3 doit exister');

        // Le fichier v2 ne doit pas avoir bougé d'un octet.
        $apres = is_file($v2) ? file_get_contents($v2) : null;
        $this->assertSame($avant, $apres, 'la sauvegarde v3 a modifie le fichier v2');

        // Et chaque tableau se relit indépendamment.
        $this->getJson('/api/suivi-sprints?tableau=v3')
             ->assertOk()->assertJsonPath('checked.s90t1', true);

        // Un nom de tableau inconnu retombe sur v2 au lieu d'ecrire n'importe ou.
        $this->getJson('/api/suivi-sprints?tableau=../../etc/passwd')->assertOk();

        @unlink(storage_path('app/suivi-v3.json'));
    }
}
