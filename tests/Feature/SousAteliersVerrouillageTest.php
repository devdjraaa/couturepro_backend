<?php

namespace Tests\Feature;

use App\Models\Atelier;
use App\Models\NotificationSysteme;
use App\Models\Proprietaire;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Verrouillage des sous-ateliers excédentaires au changement de plan.
 *
 * La propagation du plan aux sous-ateliers activait les premiers (dans la
 * limite du plan) mais laissait « tels quels » ceux au-delà : après une BAISSE
 * de plan, un sous-atelier que le plan ne couvrait plus restait actif et
 * accessible. Ce test verrouille le contrat : au-delà de la limite, l'atelier
 * passe « verrouille » et une notification est émise (le propriétaire le rouvre
 * ensuite via « Déverrouiller » après une montée de plan).
 */
class SousAteliersVerrouillageTest extends TestCase
{
    use RefreshDatabase;

    public function test_les_sous_ateliers_au_dela_de_la_limite_sont_verrouilles(): void
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local', 'nom' => 'Adjovi', 'prenom' => 'Rose',
            'question_secrete' => 'q', 'reponse_secrete' => 'r', 'password' => bcrypt('x'),
        ]);

        $maitre = Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Maître',
            'type' => 'designer', 'is_maitre' => true, 'statut' => 'actif',
        ]);
        // Trois sous-ateliers, créés dans l'ordre.
        $subs = collect(['A', 'B', 'C'])->map(fn ($n) => Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => "Sous $n",
            'type' => 'designer', 'is_maitre' => false, 'statut' => 'actif',
        ]));

        // Plan qui n'autorise QU'UN seul sous-atelier.
        $svc = app(PaymentService::class);
        $m   = new \ReflectionMethod($svc, 'propagerAuxSousAteliers');
        $m->setAccessible(true);
        $m->invoke($svc, $maitre, 'atelier_mensuel', ['max_sous_ateliers' => 1], now(), now()->addDays(30), 30);

        $this->assertSame('actif', $subs[0]->fresh()->statut, 'Le 1er sous-atelier (dans la limite) reste actif.');
        $this->assertSame('verrouille', $subs[1]->fresh()->statut, 'Le 2e est verrouillé.');
        $this->assertSame('verrouille', $subs[2]->fresh()->statut, 'Le 3e est verrouillé.');

        // Une notification par atelier verrouillé, adressée au maître.
        $this->assertSame(2, NotificationSysteme::where('atelier_id', $maitre->id)
            ->where('type', 'atelier_verrouille')->count());
    }

    public function test_un_plan_sans_sous_atelier_verrouille_tout(): void
    {
        $p = Proprietaire::create([
            'telephone' => '+2299' . random_int(1000000, 9999999),
            'email' => Str::uuid() . '@test.local', 'nom' => 'Kponou', 'prenom' => 'Ola',
            'question_secrete' => 'q', 'reponse_secrete' => 'r', 'password' => bcrypt('x'),
        ]);
        $maitre = Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Maître',
            'type' => 'artisan', 'is_maitre' => true, 'statut' => 'actif',
        ]);
        $sub = Atelier::create([
            'proprietaire_id' => $p->id, 'nom' => 'Sous unique',
            'type' => 'artisan', 'is_maitre' => false, 'statut' => 'actif',
        ]);

        $svc = app(PaymentService::class);
        $m   = new \ReflectionMethod($svc, 'propagerAuxSousAteliers');
        $m->setAccessible(true);
        $m->invoke($svc, $maitre, 'atelier_mensuel', ['max_sous_ateliers' => 0], now(), now()->addDays(30), 30);

        $this->assertSame('verrouille', $sub->fresh()->statut);
    }
}
