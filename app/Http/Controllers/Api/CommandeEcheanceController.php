<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\CommandeEcheance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandeEcheanceController extends Controller
{
    // GET /commandes/{commande}/echeances
    public function index(Commande $commande): JsonResponse
    {
        return response()->json($commande->echeances);
    }

    // POST /commandes/{commande}/echeances
    public function store(Request $request, Commande $commande): JsonResponse
    {
        $data = $request->validate([
            'date_echeance' => ['required', 'date', 'after_or_equal:today'],
            'note'          => ['nullable', 'string', 'max:500'],
        ]);

        $echeance = CommandeEcheance::create([
            'commande_id'   => $commande->id,
            'date_echeance' => $data['date_echeance'],
            'note'          => $data['note'] ?? null,
            'livree'        => false,
        ]);

        return response()->json($echeance, 201);
    }

    // PUT /commandes/{commande}/echeances/{echeance}
    public function update(Request $request, Commande $commande, CommandeEcheance $echeance): JsonResponse
    {
        if ($echeance->commande_id !== $commande->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $data = $request->validate([
            'date_echeance' => ['sometimes', 'date', 'after_or_equal:today'],
            'note'          => ['nullable', 'string', 'max:500'],
            'livree'        => ['sometimes', 'boolean'],
        ]);

        if (isset($data['livree']) && $data['livree'] && !$echeance->livree) {
            $data['livree_at'] = now();
        }

        $echeance->update($data);

        return response()->json($echeance->fresh());
    }

    // DELETE /commandes/{commande}/echeances/{echeance}
    public function destroy(Commande $commande, CommandeEcheance $echeance): JsonResponse
    {
        if ($echeance->commande_id !== $commande->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $echeance->delete();

        return response()->json(['message' => 'Échéance supprimée.']);
    }
}
