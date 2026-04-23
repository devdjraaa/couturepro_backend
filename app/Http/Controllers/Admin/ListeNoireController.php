<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ListeNoire;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListeNoireController extends Controller
{
    use LogsAdminAction;

    public function index(Request $request): JsonResponse
    {
        $entrees = ListeNoire::with('admin')
            ->when($request->type, fn($q, $t) => $q->where('type', $t))
            ->orderByDesc('created_at')
            ->paginate(50);

        return response()->json($entrees);
    }

    public function store(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'type'   => ['required', 'in:telephone,email,ip'],
            'valeur' => ['required', 'string', 'max:255'],
            'raison' => ['nullable', 'string', 'max:500'],
        ]);

        if (ListeNoire::where('type', $data['type'])->where('valeur', $data['valeur'])->exists()) {
            return response()->json(['message' => 'Cette entrée existe déjà dans la liste noire.'], 422);
        }

        $data['admin_id'] = $admin->id;
        $entree = ListeNoire::create($data);

        $this->audit($admin, 'blacklist.ajouter', 'liste_noire', $entree->id, [
            'type'   => $entree->type,
            'valeur' => $entree->valeur,
        ], $request->ip());

        return response()->json($entree, 201);
    }

    public function destroy(Request $request, ListeNoire $listeNoire): JsonResponse
    {
        $admin = auth('admin')->user();

        $this->audit($admin, 'blacklist.retirer', 'liste_noire', $listeNoire->id, [
            'type'   => $listeNoire->type,
            'valeur' => $listeNoire->valeur,
        ], $request->ip());

        $listeNoire->delete();

        return response()->json(['message' => 'Entrée retirée de la liste noire.']);
    }
}
