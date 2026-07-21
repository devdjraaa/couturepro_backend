<?php

namespace Tests\Feature;

use App\Services\QualitePhotoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * PHOTO-1 — Les codes du contrôle qualité sont un CONTRAT avec l'écran.
 *
 * Le retour au créateur est purement visuel : une icône par cause de refus,
 * sans texte. Cette correspondance code → icône vit côté écran
 * (`VerdictQualite.jsx`). Si le serveur se met à émettre un code inconnu, il
 * n'y a ni icône ni message — le créateur voit sa photo refusée SANS AUCUNE
 * indication de ce qu'il doit changer.
 *
 * Ce test fige donc la liste. Ajouter un code au service oblige à l'ajouter
 * aussi à l'écran, sinon la suite échoue ici.
 */
class QualitePhotoCodesTest extends TestCase
{
    use RefreshDatabase;

    /** Doit rester identique aux clés de PICTOS dans VerdictQualite.jsx. */
    private const CODES_CONNUS = ['resolution', 'cadrage', 'luminosite', 'nettete'];

    public function test_le_service_n_emet_que_des_codes_connus_de_l_ecran(): void
    {
        $source = file_get_contents(app_path('Services/QualitePhotoService.php'));

        preg_match_all(
            "/(?:problemes|avertissements)\[\]\s*=\s*'([a-z_]+)'/",
            $source,
            $trouves,
        );

        $emis = array_unique($trouves[1]);
        $this->assertNotEmpty($emis, 'aucun code détecté — le format du service a changé');

        foreach ($emis as $code) {
            $this->assertContains(
                $code,
                self::CODES_CONNUS,
                "Le code « {$code} » est émis par le serveur mais l'écran ne lui associe aucune icône. "
                . "Ajoutez-le à PICTOS dans VerdictQualite.jsx et aux traductions realisations.qualite.*, "
                . "sinon le créateur verra sa photo refusée sans savoir quoi corriger.",
            );
        }
    }

    public function test_les_seuils_restent_editables_en_admin(): void
    {
        // Les seuils décident de ce qui est refusé : ils ne doivent jamais
        // redevenir des valeurs figées dans le code.
        $seuils = app(QualitePhotoService::class)->seuils();

        foreach (['largeur_min', 'hauteur_min', 'luminosite_min', 'luminosite_max', 'nettete_min'] as $cle) {
            $this->assertArrayHasKey($cle, $seuils);
        }
    }
}
