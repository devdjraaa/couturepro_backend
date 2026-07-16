<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreationDesigner;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CreationDesignerController extends Controller
{
    use ResolvesAtelier;

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $query = CreationDesigner::where('atelier_id', $atelier->id)->latest();

        if ($request->filled('categorie')) {
            $query->where('categorie', $request->input('categorie'));
        }

        return response()->json($query->get());
    }

    public function store(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        // Le front envoie metadata en JSON (FormData) → décoder avant la validation array.
        if (is_string($request->input('metadata'))) {
            $request->merge(['metadata' => json_decode($request->input('metadata'), true) ?? []]);
        }

        $data = $request->validate([
            'categorie'   => ['required', 'in:croquis,fiche_technique,patron,moodboard'],
            'titre'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'metadata'    => ['nullable', 'array'],
            'public'      => ['sometimes', 'boolean'],
            'images'      => ['nullable', 'array', 'max:10'],
            'images.*'    => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $paths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $paths[] = $file->store("creations-designer/{$atelier->id}", 'public');
            }
        }

        $creation = CreationDesigner::create([
            'atelier_id'  => $atelier->id,
            'categorie'   => $data['categorie'],
            'titre'       => $data['titre'],
            'description' => $data['description'] ?? null,
            'images'      => $paths ?: null,
            'metadata'    => $data['metadata'] ?? null,
            'public'      => $data['public'] ?? false,
        ]);

        return response()->json($creation, 201);
    }

    public function show(Request $request, CreationDesigner $creation): JsonResponse
    {
        $this->authorizeCreation($request, $creation);

        return response()->json($creation);
    }

    public function update(Request $request, CreationDesigner $creation): JsonResponse
    {
        $this->authorizeCreation($request, $creation);

        // Même décodage qu'au store : metadata arrive en JSON via FormData.
        if (is_string($request->input('metadata'))) {
            $request->merge(['metadata' => json_decode($request->input('metadata'), true) ?? []]);
        }

        $data = $request->validate([
            'titre'       => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'metadata'    => ['nullable', 'array'],
            'public'      => ['sometimes', 'boolean'],
            'images'      => ['nullable', 'array', 'max:10'],
            'images.*'    => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        if ($request->hasFile('images')) {
            if ($creation->images) {
                foreach ($creation->images as $old) {
                    Storage::disk('public')->delete($old);
                }
            }
            $paths = [];
            foreach ($request->file('images') as $file) {
                $paths[] = $file->store("creations-designer/{$creation->atelier_id}", 'public');
            }
            $data['images'] = $paths;
        }

        $creation->update($data);

        return response()->json($creation);
    }

    public function destroy(Request $request, CreationDesigner $creation): JsonResponse
    {
        $this->authorizeCreation($request, $creation);

        if ($creation->images) {
            foreach ($creation->images as $path) {
                Storage::disk('public')->delete($path);
            }
        }

        $creation->delete();

        return response()->json(null, 204);
    }

    private function authorizeCreation(Request $request, CreationDesigner $creation): void
    {
        $atelier = $this->getAtelier($request);
        abort_unless($creation->atelier_id === $atelier->id, 403);
    }
}
