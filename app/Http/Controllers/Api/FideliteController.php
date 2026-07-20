<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\NotificationSysteme;
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

        // PL-9 : programme de fidélité avancé (Studio) — paliers cumulés + prochain palier.
        $paliers = null;
        if (! empty($config['fidelite_avancee'])) {
            $cumul = (int) PointsHistorique::where('atelier_id', $atelier->id)
                ->where('points', '>', 0)
                ->sum('points');

            // Paliers éditables en admin (étaient codés en dur : impossible de
            // recalibrer le programme sans redéploiement).
            $seuils = \App\Models\VitrineSetting::paliersFidelite();

            $actuel = $seuils[0];
            $prochain = null;
            foreach ($seuils as $i => $p) {
                if ($cumul >= $p['seuil']) {
                    $actuel = $p;
                    $prochain = $seuils[$i + 1] ?? null;
                }
            }

            $paliers = [
                'cumul_pts'      => $cumul,
                'palier_actuel'  => $actuel,
                'palier_suivant' => $prochain,
                'restant_pts'    => $prochain ? max(0, $prochain['seuil'] - $cumul) : 0,
                'echelle'        => $seuils,
            ];
        }

        return response()->json([
            'solde_pts'          => $solde->solde_pts,
            'seuil_conversion'   => (int) ($config['seuil_conversion_pts'] ?? 0),
            'bonus_actif'        => $atelier->abonnement?->bonus_actif ?? false,
            'bonus_jours_restants' => $atelier->abonnement?->bonus_jours_restants ?? 0,
            'fidelite_avancee'   => ! empty($config['fidelite_avancee']),
            'paliers'            => $paliers,
            'historique'         => $historique,
        ]);
    }

    public function convertir(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        // La permission `points.convert` était DÉCLARÉE dans le référentiel d'équipe
        // mais jamais vérifiée : n'importe quel membre pouvait consommer les points
        // de l'atelier. Le propriétaire n'est pas un membre d'équipe : il n'est donc
        // pas concerné par ce contrôle.
        $user = $request->user();
        if ($user instanceof \App\Models\EquipeMembre) {
            $permissions = \App\Models\PermissionEquipe::getForAtelier($atelier->id, $user->role);
            if (! in_array('points.convert', $permissions, true)) {
                return response()->json([
                    'message' => 'Votre rôle ne permet pas de convertir les points de fidélité.',
                    'code'    => 'permission_refusee',
                ], 403);
            }
        }

        try {
            $abonnement = $this->service->convertirEnBonus($atelier);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => 'Points convertis',
            'contenu'    => $abonnement->bonus_jours_restants . ' jours de bonus ont été ajoutés à votre abonnement.',
            'type'       => 'points_convertis',
            'lien'       => '/fidelite',
            'is_read'    => false,
        ]);

        return response()->json([
            'message'             => 'Conversion réussie. Bonus de ' . $abonnement->bonus_jours_restants . ' jours activé.',
            'bonus_actif'         => true,
            'bonus_jours_restants'=> $abonnement->bonus_jours_restants,
            'bonus_timestamp_debut' => $abonnement->bonus_timestamp_debut,
        ]);
    }

}
