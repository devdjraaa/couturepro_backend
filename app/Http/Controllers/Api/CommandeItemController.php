<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\CommandeItem;
use App\Models\Vetement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommandeItemController extends Controller
{
    use AuthorizesRequests;

    // GET /commandes/{commande}/items
    public function index(Commande $commande): JsonResponse
    {
        // Le middleware de permission ne contrôle que le RÔLE, pas la
        // propriété : sans ceci, l'UUID d'une commande d'un autre atelier
        // suffisait à la lire et à la modifier.
        $this->authorize('view', $commande);

        return response()->json($commande->items()->with('vetement:id,nom')->get());
    }

    // POST /commandes/{commande}/items
    public function store(Request $request, Commande $commande): JsonResponse
    {
        $this->authorize('update', $commande);

        $data = $request->validate([
            'items'                 => ['required', 'array', 'min:1'],
            'items.*.vetement_id'   => ['nullable', 'uuid', 'exists:vetements,id'],
            'items.*.vetement_nom'  => ['nullable', 'string', 'max:150'],
            'items.*.quantite'      => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
            'items.*.description'   => ['nullable', 'string', 'max:500'],
        ]);

        $created = [];
        foreach ($data['items'] as $itemData) {
            // Résoudre le nom du vêtement si non fourni
            if (empty($itemData['vetement_nom']) && !empty($itemData['vetement_id'])) {
                $itemData['vetement_nom'] = Vetement::find($itemData['vetement_id'])?->nom;
            }
            $itemData['commande_id'] = $commande->id;
            $created[] = CommandeItem::create($itemData);
        }

        // Recalculer le prix total de la commande depuis les items
        $total = CommandeItem::where('commande_id', $commande->id)
            ->selectRaw('SUM(quantite * prix_unitaire) as total')
            ->value('total') ?? 0;

        $commande->update(['prix' => $total]);

        return response()->json($created, 201);
    }

    // PUT /commandes/{commande}/items/{item}
    public function update(Request $request, Commande $commande, CommandeItem $item): JsonResponse
    {
        $this->authorize('update', $commande);

        if ($item->commande_id !== $commande->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $data = $request->validate([
            'vetement_id'   => ['nullable', 'uuid', 'exists:vetements,id'],
            'vetement_nom'  => ['nullable', 'string', 'max:150'],
            'quantite'      => ['sometimes', 'integer', 'min:1', 'max:999'],
            'prix_unitaire' => ['sometimes', 'numeric', 'min:0'],
            'description'   => ['nullable', 'string', 'max:500'],
        ]);

        $item->update($data);

        $total = CommandeItem::where('commande_id', $commande->id)
            ->selectRaw('SUM(quantite * prix_unitaire) as total')
            ->value('total') ?? 0;

        $commande->update(['prix' => $total]);

        return response()->json($item->fresh());
    }

    // DELETE /commandes/{commande}/items/{item}
    public function destroy(Commande $commande, CommandeItem $item): JsonResponse
    {
        $this->authorize('update', $commande);

        if ($item->commande_id !== $commande->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $item->delete();

        $total = CommandeItem::where('commande_id', $commande->id)
            ->selectRaw('SUM(quantite * prix_unitaire) as total')
            ->value('total') ?? 0;

        $commande->update(['prix' => $total]);

        return response()->json(['message' => 'Item supprimé.']);
    }
}
