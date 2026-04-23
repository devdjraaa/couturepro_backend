<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NiveauConfig;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NiveauConfigController extends Controller
{
    use LogsAdminAction;

    public function index(): JsonResponse
    {
        $plans = NiveauConfig::with('updatedBy')
            ->withCount('abonnements')
            ->orderBy('ordre_affichage')
            ->get();

        return response()->json($plans);
    }

    public function show(NiveauConfig $plan): JsonResponse
    {
        $plan->load('updatedBy');
        $plan->loadCount(['abonnements', 'paiements', 'transactions']);

        return response()->json($plan);
    }

    public function store(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'cle'                          => ['required', 'string', 'max:50', 'unique:niveaux_config,cle'],
            'label'                        => ['required', 'string', 'max:100'],
            'duree_jours'                  => ['required', 'integer', 'min:1'],
            'prix_xof'                     => ['required', 'numeric', 'min:0'],
            'prix_mensuel_equivalent_xof'  => ['nullable', 'numeric', 'min:0'],
            'config'                       => ['required', 'array'],
            'ordre_affichage'              => ['nullable', 'integer'],
            'description_courte'           => ['nullable', 'string', 'max:255'],
        ]);

        $data['updated_by'] = $admin->id;
        $data['is_actif']   = true;

        $plan = NiveauConfig::create($data);

        $this->audit($admin, 'plan.create', 'plan', (string) $plan->id, ['cle' => $plan->cle], request()->ip());

        return response()->json($plan, 201);
    }

    public function update(Request $request, NiveauConfig $plan): JsonResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'label'                        => ['sometimes', 'string', 'max:100'],
            'duree_jours'                  => ['sometimes', 'integer', 'min:1'],
            'prix_xof'                     => ['sometimes', 'numeric', 'min:0'],
            'prix_mensuel_equivalent_xof'  => ['nullable', 'numeric', 'min:0'],
            'config'                       => ['sometimes', 'array'],
            'ordre_affichage'              => ['nullable', 'integer'],
            'description_courte'           => ['nullable', 'string', 'max:255'],
        ]);

        $data['updated_by'] = $admin->id;

        $plan->update($data);

        $this->audit($admin, 'plan.update', 'plan', (string) $plan->id, ['cle' => $plan->cle, 'champs' => array_keys($data)], $request->ip());

        return response()->json($plan->fresh());
    }

    public function toggle(Request $request, NiveauConfig $plan): JsonResponse
    {
        $admin = auth('admin')->user();

        $plan->update([
            'is_actif'   => ! $plan->is_actif,
            'updated_by' => $admin->id,
        ]);

        $this->audit($admin, $plan->is_actif ? 'plan.activer' : 'plan.desactiver', 'plan', (string) $plan->id, ['cle' => $plan->cle], $request->ip());

        return response()->json(['is_actif' => $plan->is_actif]);
    }
}
