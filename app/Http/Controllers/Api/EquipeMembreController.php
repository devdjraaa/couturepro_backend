<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EquipeMembreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $membres = EquipeMembre::where('atelier_id', $atelier->id)
            ->where('is_active', true)
            ->get(['id', 'nom', 'prenom', 'role', 'code_acces', 'derniere_sync_at', 'created_at']);

        return response()->json($membres);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'    => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'role'   => ['required', 'in:assistant,membre'],
        ]);

        $atelier = $this->getAtelier($request);

        $config  = $atelier->abonnement?->getConfigEffective() ?? [];
        $max     = (int) ($config['max_membres'] ?? 0);
        $count   = EquipeMembre::where('atelier_id', $atelier->id)->where('is_active', true)->count();

        if ($max > 0 && $count >= $max) {
            return response()->json([
                'message' => "Limite de membres atteinte pour votre plan ({$max} max).",
            ], 403);
        }

        do {
            $code = strtoupper(Str::random(8));
        } while (EquipeMembre::where('code_acces', $code)->exists());

        $membre = EquipeMembre::create([
            'atelier_id' => $atelier->id,
            'created_by' => $request->user()->id,
            'nom'        => $data['nom'],
            'prenom'     => $data['prenom'],
            'role'       => $data['role'],
            'code_acces' => $code,
            'password'   => bcrypt($code),
            'is_active'  => true,
        ]);

        return response()->json($membre, 201);
    }

    public function destroy(Request $request, EquipeMembre $membre): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($membre->atelier_id !== $atelier->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $membre->update([
            'is_active'  => false,
            'revoque_at' => now(),
        ]);

        return response()->json(['message' => 'Membre révoqué.']);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();
        return $user instanceof \App\Models\EquipeMembre
            ? $user->atelier
            : $user->atelierMaitre;
    }
}
