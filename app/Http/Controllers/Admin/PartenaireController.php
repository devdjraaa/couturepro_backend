<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CandidaturePartenaire;
use App\Models\Partenaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// P204 : gestion admin des partenaires + candidatures (validation manuelle).
class PartenaireController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Partenaire::orderBy('ordre')->orderBy('nom')->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('partenaires', 'public');
        }

        return response()->json(Partenaire::create($data), 201);
    }

    public function update(Request $request, Partenaire $partenaire): JsonResponse
    {
        $data = $this->validated($request, $partenaire);

        if ($request->hasFile('logo')) {
            if ($partenaire->logo_path) {
                Storage::disk('public')->delete($partenaire->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('partenaires', 'public');
        }

        $partenaire->update($data);

        return response()->json($partenaire->fresh());
    }

    public function destroy(Partenaire $partenaire): JsonResponse
    {
        if ($partenaire->logo_path) {
            Storage::disk('public')->delete($partenaire->logo_path);
        }
        $partenaire->delete();

        return response()->json(['message' => 'Partenaire supprimé.']);
    }

    // ── Candidatures ────────────────────────────────────────────────────────
    public function candidatures(Request $request): JsonResponse
    {
        $q = CandidaturePartenaire::latest();
        if ($request->filled('statut')) {
            $q->where('statut', $request->input('statut'));
        }

        return response()->json($q->get());
    }

    /**
     * Change le statut d'une candidature. La validation ne déclenche AUCUN envoi
     * automatique de document — les conventions/NDA restent envoyées manuellement.
     */
    public function statutCandidature(Request $request, CandidaturePartenaire $candidature): JsonResponse
    {
        $data = $request->validate([
            'statut' => ['required', 'in:en_attente,validee,rejetee'],
        ]);

        $candidature->update($data);

        return response()->json($candidature->fresh());
    }

    private function validated(Request $request, ?Partenaire $existant = null): array
    {
        return $request->validate([
            'nom'         => [$existant ? 'sometimes' : 'required', 'string', 'max:180'],
            'categorie'   => [$existant ? 'sometimes' : 'required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:2000'],
            'site_url'    => ['nullable', 'url', 'max:255'],
            'pays'        => ['nullable', 'string', 'max:120'],
            'actif'       => ['sometimes', 'boolean'],
            'is_cle'      => ['sometimes', 'boolean'],
            'ordre'       => ['sometimes', 'integer', 'min:0'],
            'logo'        => ['sometimes', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
        ]);
    }
}
