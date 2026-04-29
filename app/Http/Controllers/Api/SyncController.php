<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Http\Requests\Api\SyncPushRequest;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    use ResolvesAtelier;
    public function __construct(private SyncService $syncService) {}

    public function push(SyncPushRequest $request): JsonResponse
    {
        $atelier    = $this->getAtelier($request);
        $user       = $request->user();
        $actorRole  = $user instanceof EquipeMembre ? $user->role : 'proprietaire';

        $results = $this->syncService->push(
            $atelier,
            $request->operations,
            $user->id,
            $actorRole
        );

        return response()->json([
            'results' => $results,
        ]);
    }

    public function pull(Request $request): JsonResponse
    {
        $atelier      = $this->getAtelier($request);
        $lastPulledAt = $request->query('last_pulled_at');

        $data = $this->syncService->pull($atelier, $lastPulledAt);

        return response()->json($data);
    }

}
