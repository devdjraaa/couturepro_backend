<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OffreSpeciale;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OffreSpecialeController extends Controller
{
    use LogsAdminAction;

    public function index(Request $request): JsonResponse
    {
        $offres = OffreSpeciale::with(['atelier.proprietaire', 'admin', 'niveauBase'])
            ->when($request->statut, fn($q, $s) => $q->where('statut', $s))
            ->when($request->atelier_id, fn($q, $id) => $q->where('atelier_id', $id))
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($offres);
    }

    public function store(Request $request): JsonResponse
    {
        $admin = $this->adminUser();

        $data = $request->validate([
            'atelier_id'      => ['required', 'uuid', 'exists:ateliers,id'],
            'label'           => ['required', 'string', 'max:150'],
            'niveau_base_cle' => ['required', 'string', 'exists:niveaux_config,cle'],
            'config_override' => ['required', 'array'],
            'prix_special'    => ['nullable', 'numeric', 'min:0'],
            'duree_jours'     => ['required', 'integer', 'min:1'],
            'expire_at'       => ['nullable', 'date'],
            'notes_internes'  => ['nullable', 'string'],
        ]);

        $data['admin_id'] = $admin->id;
        $data['statut']   = 'actif';

        $offre = OffreSpeciale::create($data);

        $this->audit($admin, 'offre.create', 'offre_speciale', $offre->id, [
            'label'      => $offre->label,
            'atelier_id' => $offre->atelier_id,
        ], $request->ip());

        return response()->json($offre->load(['atelier', 'niveauBase']), 201);
    }

    public function update(Request $request, OffreSpeciale $offre): JsonResponse
    {
        $admin = $this->adminUser();

        $data = $request->validate([
            'label'           => ['sometimes', 'string', 'max:150'],
            'config_override' => ['sometimes', 'array'],
            'prix_special'    => ['nullable', 'numeric', 'min:0'],
            'duree_jours'     => ['sometimes', 'integer', 'min:1'],
            'statut'          => ['sometimes', 'in:actif,expire,annule'],
            'expire_at'       => ['nullable', 'date'],
            'notes_internes'  => ['nullable', 'string'],
        ]);

        $offre->update($data);

        $this->audit($admin, 'offre.update', 'offre_speciale', $offre->id, ['label' => $offre->label], $request->ip());

        return response()->json($offre->fresh());
    }

    public function destroy(Request $request, OffreSpeciale $offre): JsonResponse
    {
        $admin = $this->adminUser();

        $this->audit($admin, 'offre.delete', 'offre_speciale', $offre->id, ['label' => $offre->label], $request->ip());

        $offre->delete();

        return response()->json(['message' => 'Offre supprimée.']);
    }
}
