<?php

namespace Tests\Feature;

use App\Services\DeviseService;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CLI-1 — Détection du pays et de la devise.
 *
 * Le piège de ce genre de table est l'IMBRICATION des indicatifs : « +1 »
 * préfixe « +1868 », « +22 » préfixe « +229 ». Comparés dans l'ordre naturel,
 * ces pays seraient tous rattachés au mauvais indicatif — et le défaut ne se
 * voit qu'avec les pays concernés, jamais avec le Bénin qui sert aux essais.
 */
class DeviseServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): DeviseService
    {
        return app(DeviseService::class);
    }

    public static function numeros(): array
    {
        return [
            'Bénin'        => ['+22990001122', 'BJ', 'XOF'],
            'Ghana'        => ['+233241234567', 'GH', 'GHS'],
            'Nigéria'      => ['+2348012345678', 'NG', 'NGN'],
            'Cameroun'     => ['+237670000000', 'CM', 'XAF'],
            'Côte dIvoire' => ['+2250700000000', 'CI', 'XOF'],
            'France'       => ['+33612345678', 'FR', 'EUR'],
            'Égypte'       => ['+201000000000', 'EG', 'EGP'],
        ];
    }

    #[DataProvider('numeros')]
    public function test_le_pays_et_la_devise_se_deduisent_du_numero(string $tel, string $pays, string $devise): void
    {
        $this->assertSame($pays, $this->service()->paysDepuisTelephone($tel));
        $this->assertSame($devise, $this->service()->deviseDepuisTelephone($tel));
    }

    public function test_un_indicatif_court_ne_capte_pas_un_indicatif_long(): void
    {
        // « +22 » n'existe pas comme pays, mais « +220 » (Gambie), « +221 »
        // (Sénégal) et « +229 » (Bénin) se ressemblent. Et « +20 » (Égypte)
        // préfixe « +229 » si l'on compare sans le « + ».
        $this->assertSame('GM', $this->service()->paysDepuisTelephone('+2207778888'));
        $this->assertSame('SN', $this->service()->paysDepuisTelephone('+221770000000'));
        $this->assertSame('BJ', $this->service()->paysDepuisTelephone('+22990001122'));
    }

    public function test_un_numero_sans_indicatif_ne_donne_aucun_pays(): void
    {
        // Deviner ici reviendrait à supposer le Bénin pour le monde entier.
        $this->assertNull($this->service()->paysDepuisTelephone('90001122'));
        $this->assertNull($this->service()->paysDepuisTelephone('0090001122'));
    }

    public function test_un_numero_vide_ou_absent_ne_casse_rien(): void
    {
        $this->assertNull($this->service()->paysDepuisTelephone(null));
        $this->assertNull($this->service()->paysDepuisTelephone(''));
        // Et la devise retombe sur le défaut plutôt que d'échouer.
        $this->assertSame('XOF', $this->service()->deviseDepuisTelephone(null));
    }

    public function test_les_espaces_et_separateurs_sont_tolérés(): void
    {
        // Le champ de saisie compose « +229 90 00 11 22 ».
        $this->assertSame('BJ', $this->service()->paysDepuisTelephone('+229 90 00 11 22'));
        $this->assertSame('GH', $this->service()->paysDepuisTelephone('+233-24-123-4567'));
    }

    public function test_un_pays_inconnu_retombe_sur_la_devise_par_defaut(): void
    {
        $this->assertSame('XOF', $this->service()->devisePourPays('ZZ'));
        $this->assertSame('XOF', $this->service()->devisePourPays(null));
    }

    public function test_les_decimales_suivent_la_devise(): void
    {
        // Le franc CFA ne se divise pas ; le cedi et le naira si. Formater un
        // montant ghanéen sans décimale fausserait la facture.
        $this->assertSame(0, $this->service()->format('XOF')['decimales']);
        $this->assertSame(0, $this->service()->format('XAF')['decimales']);
        $this->assertSame(2, $this->service()->format('GHS')['decimales']);
        $this->assertSame(2, $this->service()->format('NGN')['decimales']);
        $this->assertSame(3, $this->service()->format('TND')['decimales']);
    }

    public function test_une_devise_inconnue_reste_affichable(): void
    {
        // On ne fait jamais échouer un affichage de montant : le code de la
        // devise sert alors de symbole.
        $f = $this->service()->format('ZZZ');
        $this->assertSame('ZZZ', $f['symbole']);
        $this->assertArrayHasKey('decimales', $f);
    }

    public function test_le_referentiel_servi_au_front_est_complet(): void
    {
        $r = $this->service()->referentiel();

        $this->assertArrayHasKey('defaut', $r);
        $this->assertNotEmpty($r['formats']);
        // Chaque format doit porter les deux informations dont l'écran a besoin.
        foreach ($r['formats'] as $devise => $f) {
            $this->assertArrayHasKey('symbole', $f, "symbole manquant pour {$devise}");
            $this->assertArrayHasKey('decimales', $f, "decimales manquantes pour {$devise}");
        }
    }

    public function test_le_montant_habille_suit_la_devise(): void
    {
        $d = $this->service();

        // Le franc CFA sans décimale, le cedi avec — c'est ce qui part dans les
        // messages WhatsApp envoyés aux clients de l'atelier.
        $this->assertSame('125 000 FCFA', $d->montant(125000, 'XOF'));
        $this->assertSame('1 250,50 GH₵', $d->montant(1250.5, 'GHS'));
        $this->assertSame('12 000,00 ₦', $d->montant(12000, 'NGN'));
    }

    public function test_un_montant_absent_vaut_zero_et_ne_casse_rien(): void
    {
        // Un message ne doit jamais partir avec un montant vide ou une erreur.
        $this->assertSame('0 FCFA', $this->service()->montant(null, 'XOF'));
        $this->assertSame('0 FCFA', $this->service()->montant(0, 'XOF'));
    }

    public function test_la_devise_d_un_atelier_sans_parametres_retombe_sur_le_defaut(): void
    {
        // Cas réel : un atelier créé avant l'introduction du réglage.
        $this->assertSame('XOF', $this->service()->deviseAtelier(null));
    }
}
