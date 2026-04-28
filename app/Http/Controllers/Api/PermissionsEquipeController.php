<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\PermissionEquipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionsEquipeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $role    = $request->validate(['role' => ['required', 'in:assistant,membre']])['role'];

        return response()->json([
            'role'        => $role,
            'permissions' => PermissionEquipe::getForAtelier($atelier->id, $role),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data    = $request->validate([
            'role'          => ['required', 'in:assistant,membre'],
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string', 'in:' . implode(',', PermissionEquipe::ALL_PERMISSIONS)],
        ]);

        $atelier = $this->getAtelier($request);

        foreach (PermissionEquipe::ALL_PERMISSIONS as $perm) {
            [$ressource, $action] = explode('.', $perm, 2);
            PermissionEquipe::updateOrCreate(
                [
                    'atelier_id' => $atelier->id,
                    'role'       => $data['role'],
                    'ressource'  => $ressource,
                    'action'     => $action,
                ],
                ['autorise' => in_array($perm, $data['permissions'])]
            );
        }

        return response()->json([
            'role'        => $data['role'],
            'permissions' => PermissionEquipe::getForAtelier($atelier->id, $data['role']),
        ]);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();

        // Seul le propriétaire peut gérer les permissions
        if (!$user instanceof \App\Models\Proprietaire) {
            abort(403, 'Accès réservé au propriétaire.');
        }

        return $user->atelierMaitre;
    }
}
