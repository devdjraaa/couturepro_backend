<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\PointsFidelite;
use App\Models\PointsHistorique;
use App\Services\PointsFideliteService;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FideliteController extends Controller
{
    use ResolvesAtelier;

    public function __construct(private readonly PointsFideliteService $service) {}

    public function show(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $solde = PointsFidelite::firstOrCreate(
            ['atelier_id' => $atelier->id],
            ['solde_pts'  => 0]
        );

        $historique = PointsHistorique::where('atelier_id', $atelier->id)
            ->orderByDesc('created_at')
            ->paginate(30);

        $config = $atelier->abonnement?->getConfigEffective() ?? [];

        return response()->json([
            'solde_pts'          => $solde->solde_pts,
            'seuil_conversion'   => (int) ($config['seuil_conversion_pts'] ?? 0),
            'bonus_actif'        => $atelier->abonnement?->bonus_actif ?? false,
            'bonus_jours_restants' => $atelier->abonnement?->bonus_jours_restants ?? 0,
            'historique'         => $historique,
        ]);
    }

    public function convertir(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        try {
            $abonnement = $this->service->convertirEnBonus($atelier);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message'             => 'Conversion réussie. Bonus de 31 jours activé.',
            'bonus_actif'         => true,
            'bonus_jours_restants'=> $abonnement->bonus_jours_restants,
            'bonus_timestamp_debut' => $abonnement->bonus_timestamp_debut,
        ]);
    }

}
