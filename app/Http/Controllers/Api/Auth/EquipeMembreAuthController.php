<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EquipeLoginRequest;
use App\Models\EquipeMembre;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class EquipeMembreAuthController extends Controller
{
    public function login(EquipeLoginRequest $request): JsonResponse
    {
        $membre = EquipeMembre::where('code_acces', $request->code_acces)->first();

        if (!$membre || !Hash::check($request->password, $membre->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        if (!$membre->is_active) {
            return response()->json(['message' => 'Ce compte a été désactivé.'], 403);
        }

        if ($membre->isDeviceLocked() && $membre->device_id !== $request->device_id) {
            return response()->json(['message' => 'Appareil non autorisé. Contactez votre responsable.'], 403);
        }

        if (!$membre->isDeviceLocked()) {
            $membre->update([
                'device_id'        => $request->device_id,
                'device_locked_at' => now(),
            ]);
        }

        $membre->update(['derniere_sync_at' => now()]);

        $token = $membre->createToken('equipe_token')->plainTextToken;

        return response()->json([
            'token'  => $token,
            'membre' => $membre->only(['id', 'nom', 'prenom', 'role', 'code_acces', 'atelier_id']),
        ]);
    }
}
