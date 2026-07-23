<?php

namespace Tests\Feature;

use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\NiveauConfig;
use App\Models\OtpToken;
use App\Models\Proprietaire;
use App\Models\VitrineSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Durée de l'essai offert.
 *
 * Elle était écrite en dur à TROIS endroits du contrôleur d'inscription
 * (`addDays(14)` deux fois et `jours_restants => 14`). Deux conséquences :
 * la changer demandait un déploiement, et surtout rien ne garantissait que la
 * page publique des tarifs annonce la même durée que celle réellement
 * accordée — on pouvait promettre 14 jours et n'en donner que 7.
 *
 * La durée vient désormais des réglages de tarification, qui alimentent AUSSI
 * la page publique. Ces tests verrouillent ce lien : c'est exactement le genre
 * de valeur qui redérive en silence.
 */
class EssaiGratuitTest extends TestCase
{
    use RefreshDatabase;

    /** Inscrit un compte de bout en bout (inscription puis vérification OTP). */
    private function inscrire(string $type = 'artisan'): Atelier
    {
        // Les niveaux d'essai doivent exister, sinon l'abonnement part sans config.
        foreach (['standard_mensuel', 'master_mensuel'] as $cle) {
            NiveauConfig::firstOrCreate(['cle' => $cle], [
                'label'       => ucfirst($cle),
                'prix_xof'    => 2500,
                'duree_jours' => 30,
                'config'      => ['max_membres' => 1],
            ]);
        }

        $telephone = '+2299' . random_int(1000000, 9999999);

        $this->postJson('/api/auth/inscription', [
            'nom' => 'Sossou', 'prenom' => 'Awa',
            'nom_atelier' => 'Atelier Awa', 'type' => $type,
            'telephone' => $telephone, 'email' => Str::uuid() . '@test.local',
            'password' => 'motdepasse123', 'password_confirmation' => 'motdepasse123',
            'question_secrete' => 'Nom de votre premier animal ?', 'reponse_secrete' => 'bleu',
        ])->assertCreated();

        $code = OtpToken::where('telephone', Proprietaire::normalizePhone($telephone))
            ->whereNull('used_at')->latest('created_at')->value('code');

        $this->postJson('/api/auth/verifier-otp', ['telephone' => $telephone, 'code' => $code])
             ->assertOk();

        return Atelier::where('nom', 'Atelier Awa')->latest('created_at')->firstOrFail();
    }

    public function test_la_duree_par_defaut_est_bien_celle_annoncee_sur_les_tarifs(): void
    {
        $annonce = (int) VitrineSetting::tarification()['essai_jours'];

        $atelier    = $this->inscrire();
        $abonnement = Abonnement::where('atelier_id', $atelier->id)->firstOrFail();

        $this->assertSame($annonce, (int) $abonnement->jours_restants);
        // Tolérance d'une minute : l'écriture et l'assertion ne sont pas au même instant.
        $this->assertEqualsWithDelta(
            now()->addDays($annonce)->timestamp,
            $abonnement->timestamp_expiration->timestamp,
            60,
        );
    }

    public function test_changer_le_reglage_change_l_essai_reellement_accorde(): void
    {
        VitrineSetting::updateOrCreate(['cle' => 'tarification'], ['valeur' => ['essai_jours' => 7]]);

        $atelier    = $this->inscrire();
        $abonnement = Abonnement::where('atelier_id', $atelier->id)->firstOrFail();

        // 7 et non 14 : c'est tout l'intérêt de la manœuvre.
        $this->assertSame(7, (int) $abonnement->jours_restants);
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $abonnement->timestamp_expiration->timestamp, 60);
        $this->assertEqualsWithDelta(now()->addDays(7)->timestamp, $atelier->essai_expire_at->timestamp, 60);
    }

    public function test_un_reglage_absurde_ne_supprime_pas_l_essai(): void
    {
        // Un zéro ou un négatif saisi en administration ne doit pas créer un
        // compte déjà expiré : le plancher est d'un jour.
        VitrineSetting::updateOrCreate(['cle' => 'tarification'], ['valeur' => ['essai_jours' => 0]]);

        $atelier    = $this->inscrire();
        $abonnement = Abonnement::where('atelier_id', $atelier->id)->firstOrFail();

        $this->assertGreaterThanOrEqual(1, (int) $abonnement->jours_restants);
        $this->assertTrue($atelier->essai_expire_at->isFuture());
    }

    public function test_la_page_publique_expose_la_duree_et_le_texte(): void
    {
        $r = $this->getJson('/api/vitrine/tarification')->assertOk();

        // Sans ces clés, la page ne peut pas annoncer l'essai.
        $r->assertJsonStructure(['essai_actif', 'essai_jours', 'essai_titre' => ['fr', 'en'], 'essai_texte' => ['fr', 'en']]);
        $this->assertGreaterThan(0, (int) $r->json('essai_jours'));
    }
}
