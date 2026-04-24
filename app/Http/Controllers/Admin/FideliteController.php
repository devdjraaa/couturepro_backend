<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\PointsFidelite;
use App\Models\PointsHistorique;
use App\Services\PointsFideliteService;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FideliteController extends Controller
{
    use LogsAdminAction;

    public function __construct(private readonly PointsFideliteService $service) {}

    public function show(Request $request, Atelier $atelier): JsonResponse
    {
        $solde = PointsFidelite::firstOrCreate(
            ['atelier_id' => $atelier->id],
            ['solde_pts'  => 0]
        );

        $historique = PointsHistorique::where('atelier_id', $atelier->id)
            ->orderByDesc('created_at')
            ->paginate(30);

        return response()->json([
            'atelier_id'  => $atelier->id,
            'solde_pts'   => $solde->solde_pts,
            'bonus_actif' => $atelier->abonnement?->bonus_actif ?? false,
            'historique'  => $historique,
        ]);
    }

    public function ajuster(Request $request, Atelier $atelier): JsonResponse
    {
        $admin = $this->adminUser();

        $data = $request->validate([
            'points'      => ['required', 'integer', 'not_in:0'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $solde = PointsFidelite::firstOrCreate(
            ['atelier_id' => $atelier->id],
            ['solde_pts'  => 0]
        );

        $nouveauSolde = $solde->solde_pts + $data['points'];

        if ($nouveauSolde < 0) {
            return response()->json([
                'message' => "Ajustement impossible : le solde deviendrait négatif ({$nouveauSolde} pts).",
            ], 422);
        }

        if ($data['points'] > 0) {
            $solde->increment('solde_pts', $data['points']);
        } else {
            $solde->decrement('solde_pts', abs($data['points']));
        }

        PointsHistorique::create([
            'atelier_id'  => $atelier->id,
            'type'        => 'bonus_admin',
            'points'      => $data['points'],
            'description' => $data['description'],
            'created_at'  => now(),
        ]);

        $this->audit($admin, 'fidelite.ajuster', 'atelier', $atelier->id, [
            'points'      => $data['points'],
            'description' => $data['description'],
            'solde_avant' => $solde->solde_pts - $data['points'],
            'solde_apres' => $solde->fresh()->solde_pts,
        ], $request->ip());

        return response()->json([
            'message'    => 'Ajustement effectué.',
            'solde_pts'  => $solde->fresh()->solde_pts,
            'ajustement' => $data['points'],
        ]);
    }
}
