<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Http\Requests\Api\StoreVetementRequest;
use App\Http\Requests\Api\UpdateVetementRequest;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\Vetement;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VetementController extends Controller
{
    use ResolvesAtelier;
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $vetements = Vetement::where(function ($q) use ($atelier) {
                $q->where('atelier_id', $atelier->id)
                  ->orWhere('is_systeme', true);
            })
            ->actif()
            ->get();

        return response()->json($vetements);
    }

    public function store(StoreVetementRequest $request): JsonResponse
    {
        $this->authorize('create', Vetement::class);

        $atelier = $this->getAtelier($request);
        $user    = $request->user();

        $imagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store('vetements', 'public');
            }
        } elseif ($request->hasFile('image')) {
            $imagePaths[] = $request->file('image')->store('vetements', 'public');
        }

        $vetement = Vetement::create([
            'atelier_id'      => $atelier->id,
            'nom'             => $request->nom,
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

}
