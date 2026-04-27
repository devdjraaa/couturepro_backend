<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Abonnement;
use App\Models\Atelier;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AtelierController extends Controller
{
    use LogsAdminAction;

    public function index(Request $request): JsonResponse
    {
        $ateliers = Atelier::with(['proprietaire', 'abonnement.niveau'])
            ->withCount(['clients', 'commandes'])
            ->when($request->statut, fn($q, $s) => $q->where('statut', $s))
            ->when($request->search, fn($q, $s) =>
                $q->where('nom', 'like', "%{$s}%")
                  ->orWhereHas('proprietaire', fn($q2) => $q2->where('telephone', 'like', "%{$s}%"))
            )
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($ateliers);
    }

    public function show(Atelier $atelier): JsonResponse
    {
        $atelier->load([
            'proprietaire',
            'abonnement.niveau',
            'equipesMembres',
            'quotaMoisCourant',
            'pointsFidelite',
        ]);
        $atelier->loadCount(['clients', 'commandes']);

        return response()->json($atelier);
    }

    public function geler(Request $request, Atelier $atelier): JsonResponse
    {
        if ($atelier->statut === 'gele') {
            return response()->json(['message' => 'Atelier déjà gelé.'], 422);
        }

        $admin = $this->adminUser();

        $ancienStatut = $atelier->statut;
        $atelier->update(['statut' => 'gele']);

        $this->audit($admin, 'atelier.geler', 'atelier', $atelier->id, [
            'ancien_statut' => $ancienStatut,
        ], $request->ip());

        return response()->json(['message' => 'Atelier gelé.', 'atelier' => $atelier]);
    }

    public function demo(Request $request, Atelier $atelier): JsonResponse
    {
        $data  = $request->validate(['is_demo' => ['required', 'boolean']]);
        $admin = $this->adminUser();

        $atelier->update(['is_demo' => $data['is_demo']]);

        $this->audit($admin, 'atelier.demo', 'atelier', $atelier->id, [
            'is_demo' => $data['is_demo'],
        ], $request->ip());

        return response()->json(['message' => 'Mode démo mis à jour.', 'is_demo' => $atelier->is_demo]);
    }

    public function trial(Request $request, Atelier $atelier): JsonResponse
    {
        $data = $request->validate([
            'duree' => ['required', 'integer', 'min:1'],
            'unite' => ['required', 'in:minutes,heures,jours'],
        ]);

        $admin = $this->adminUser();

        $expire = match ($data['unite']) {
            'minutes' => now()->addMinutes($data['duree']),
            'heures'  => now()->addHours($data['duree']),
            'jours'   => now()->addDays($data['duree']),
        };

        $atelier->update([
            'statut'          => 'essai',
            'essai_expire_at' => $expire,
        ]);

        $dureeJours = match ($data['unite']) {
            'jours'   => $data['duree'],
            'heures'  => max(1, (int) ceil($data['duree'] / 24)),
            'minutes' => max(1, (int) ceil($data['duree'] / 1440)),
        };

        Abonnement::updateOrCreate(
            ['atelier_id' => $atelier->id],
            [
                'statut'               => 'essai',
                'niveau_cle'           => 'standard_mensuel',
                'jours_restants'       => $dureeJours,
                'timestamp_debut'      => now(),
                'timestamp_expiration' => $expire,
                'config_snapshot'      => null,
            ]
        );

        $this->audit($admin, 'atelier.trial', 'atelier', $atelier->id, [
            'duree' => $data['duree'],
            'unite' => $data['unite'],
            'expire_at' => $expire->toISOString(),
        ], $request->ip());

        return response()->json([
            'message'          => "Période d'essai mise à jour.",
            'essai_expire_at'  => $atelier->essai_expire_at,
        ]);
    }

    public function degeler(Request $request, Atelier $atelier): JsonResponse
    {
        if ($atelier->statut !== 'gele') {
            return response()->json(['message' => "L'atelier n'est pas gelé."], 422);
        }

        $admin = $this->adminUser();

        $abonnement  = $atelier->abonnement;
        $nouveauStatut = ($abonnement && $abonnement->statut === 'actif') ? 'actif' : 'expire';

        $atelier->update(['statut' => $nouveauStatut]);

        $this->audit($admin, 'atelier.degeler', 'atelier', $atelier->id, [
            'nouveau_statut' => $nouveauStatut,
        ], $request->ip());

        return response()->json(['message' => 'Atelier dégelé.', 'atelier' => $atelier]);
    }
}
