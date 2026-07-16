<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use App\Models\Vetement;
use App\Traits\ChecksPlanFeature;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionController extends Controller
{
    use ResolvesAtelier, ChecksPlanFeature;

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        return response()->json(
            Collection::where('atelier_id', $atelier->id)
                ->withCount('vetements')
                ->orderBy('nom')
                ->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['nom' => ['required', 'string', 'max:120']]);
        $atelier = $this->getAtelier($request);

        $collection = Collection::create([
            'atelier_id' => $atelier->id,
            'nom'        => $request->nom,
        ]);

        return response()->json($collection, 201);
    }

    public function update(Request $request, Collection $collection): JsonResponse
    {
        $this->assertOwner($request, $collection);
        $request->validate(['nom' => ['required', 'string', 'max:120']]);

        $collection->update(['nom' => $request->nom]);

        return response()->json($collection);
    }

    public function destroy(Request $request, Collection $collection): JsonResponse
    {
        $this->assertOwner($request, $collection);

        // On détache les créations puis on supprime la collection.
        Vetement::where('collection_id', $collection->id)->update(['collection_id' => null]);
        $collection->delete();

        return response()->json(['message' => 'Collection supprimée.']);
    }

    /**
     * PL-6 : publie une annonce de collection (Studio) — message mis en avant sur la
     * vitrine + confirmation au propriétaire. Gaté `annonce_collection`.
     */
    public function annoncer(Request $request, Collection $collection): JsonResponse
    {
        $this->assertOwner($request, $collection);
        $atelier = $this->getAtelier($request);

        if ($gate = $this->planGate($atelier, 'annonce_collection')) {
            return $gate;
        }

        $data = $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $collection->update([
            'annonce_message' => $data['message'] ?: "Nouvelle collection : {$collection->nom}",
            'annonce_at'      => now(),
        ]);

        \App\Models\NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => 'Annonce publiée',
            'contenu'    => "Votre collection « {$collection->nom} » est mise en avant sur votre vitrine.",
            'type'       => 'annonce_collection',
            'lien'       => '/ma-vitrine',
            'is_read'    => false,
        ]);

        return response()->json($collection->fresh());
    }

    private function assertOwner(Request $request, Collection $collection): void
    {
        $atelier = $this->getAtelier($request);
        abort_unless($collection->atelier_id === $atelier->id, 403);
    }
}
