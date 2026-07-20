<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ChecksPlanFeature;
use App\Traits\ResolvesAtelier;
use App\Http\Requests\Api\StoreVetementRequest;
use App\Http\Requests\Api\UpdateVetementRequest;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\Vetement;
use App\Services\AtelierLimitsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VetementController extends Controller
{
    use ChecksPlanFeature;
    use ResolvesAtelier;
    use AuthorizesRequests;

    /**
     * S02A-28 — Plafond de photos par modèle, piloté par le plan.
     *
     * ⚠️ Le serveur n'imposait AUCUNE limite : le front s'arrêtait à 5, mais
     * l'API acceptait n'importe quel nombre d'images. Un appel direct pouvait
     * donc remplir le stockage sans rien enfreindre.
     */
    private function refuseSiTropDePhotos(Atelier $atelier, int $nombre): ?JsonResponse
    {
        $max = (int) ($atelier->abonnement?->getConfigEffective()['max_photos_vetement'] ?? 5);
        if ($max <= 0 || $nombre <= $max) {
            return null;
        }

        return response()->json([
            'message'     => "Votre formule permet {$max} photo(s) par modèle. Vous en avez envoyé {$nombre}.",
            'code'        => 'quota_photos',
            'max'         => $max,
            'plan_requis' => $this->planRequisPourLimite('max_photos_vetement', $max),
        ], 422);
    }

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $vetements = Vetement::where(function ($q) use ($atelier) {
                $q->where('atelier_id', $atelier->id)
                  ->orWhere('is_systeme', true);
            })
            ->actif()
            ->orderBy('nom') // pt 66 : catalogue classé alphabétiquement (recherche facilitée)
            ->get();

        return response()->json($vetements);
    }

    public function store(StoreVetementRequest $request): JsonResponse
    {
        $this->authorize('create', Vetement::class);

        $atelier = $this->getAtelier($request);
        $user    = $request->user();

        if ($refus = $this->refuseSiTropDePhotos($atelier, count($request->file('images') ?? []))) {
            return $refus;
        }

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store('vetements', 'public');
            }
        } elseif ($request->hasFile('image')) {
            $imagePaths[] = $request->file('image')->store('vetements', 'public');
        }

        // Pts 68-69 : libellés nettoyés — vides écartés, doublons retirés.
        $libelles = collect($request->input('libelles_mesures', []))
            ->map(fn ($l) => trim((string) $l))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $vetement = Vetement::create([
            'atelier_id'      => $atelier->id,
            'nom'             => $request->nom,
            'libelles_mesures' => $libelles ?: null,
            'image_path'      => $imagePaths[0] ?? null,
            'images'          => $imagePaths ?: null,
            'is_systeme'      => false,
            'is_archived'     => false,
            'created_by'      => $user->id,
            'created_by_role' => 'proprietaire',
        ]);

        return response()->json($vetement, 201);
    }

    public function update(UpdateVetementRequest $request, Vetement $vetement): JsonResponse
    {
        $this->authorize('update', $vetement);

        $data = ['nom' => $request->nom ?? $vetement->nom];

        // Pts 68-69 : liste des mesures attendues, modifiable après coup.
        if ($request->has('libelles_mesures')) {
            $libelles = collect($request->input('libelles_mesures', []))
                ->map(fn ($l) => trim((string) $l))
                ->filter()
                ->unique()
                ->values()
                ->all();
            $data['libelles_mesures'] = $libelles ?: null;
        }

        // Le même plafond s'applique à la modification : sinon on crée avec une
        // photo puis on « modifie » avec cinquante.
        if ($refus = $this->refuseSiTropDePhotos($this->getAtelier($request), count($request->file('images') ?? []))) {
            return $refus;
        }

        $newPaths = [];
        if ($request->hasFile('images')) {
            foreach ($vetement->images ?? [] as $old) {
                Storage::disk('public')->delete($old);
            }
            if ($vetement->image_path && !in_array($vetement->image_path, $vetement->images ?? [])) {
                Storage::disk('public')->delete($vetement->image_path);
            }
            foreach ($request->file('images') as $file) {
                $newPaths[] = $file->store('vetements', 'public');
            }
            $data['image_path'] = $newPaths[0] ?? null;
            $data['images']     = $newPaths ?: null;
        } elseif ($request->hasFile('image')) {
            if ($vetement->image_path) {
                Storage::disk('public')->delete($vetement->image_path);
            }
            $path = $request->file('image')->store('vetements', 'public');
            $data['image_path'] = $path;
            $data['images']     = [$path];
        }

        $vetement->update($data);

        return response()->json($vetement);
    }

    public function destroy(Request $request, Vetement $vetement): JsonResponse
    {
        $this->authorize('delete', $vetement);

        $vetement->update(['is_archived' => true]);

        return response()->json(['message' => 'Vêtement archivé.']);
    }

    // POST /vetements/{vetement}/publication — publie / retire la création de la vitrine.
    // Body optionnel { publie: bool } ; sans body, bascule l'état courant.
    public function togglePublication(Request $request, Vetement $vetement): JsonResponse
    {
        $this->authorize('update', $vetement);

        $publie = $request->boolean('publie', ! $vetement->publie_vitrine);

        // Limites de publication (cap simultané des plans payants + quota d'actes
        // par période du plan gratuit) — uniquement au passage en publié.
        if ($publie && ! $vetement->publie_vitrine) {
            $atelier = $this->getAtelier($request);
            $limits  = app(AtelierLimitsService::class);

            if ($refus = $limits->publicationRefus($atelier)) {
                return response()->json(['message' => $refus], 403);
            }

            $limits->logPublication($atelier, $vetement->id);
        }

        $vetement->update(['publie_vitrine' => $publie]);

        return response()->json($vetement);
    }

    // POST /vetements/{vetement}/collection — assigne (ou retire) la création à une collection.
    public function setCollection(Request $request, Vetement $vetement): JsonResponse
    {
        $this->authorize('update', $vetement);
        $request->validate(['collection_id' => ['nullable', 'string']]);

        $vetement->update(['collection_id' => $request->input('collection_id') ?: null]);

        return response()->json($vetement);
    }

}
