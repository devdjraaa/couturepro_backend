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

    /**
     * S02A-28b — Référentiel des fonctionnalités configurables.
     *
     * L'écran admin des plans portait sa PROPRE liste de clés connues, en dur.
     * Chaque nouvelle clé ajoutée par une migration se retrouvait donc reléguée
     * dans « Autres clés », affichée en brut (`max_photos_vetement`) sans libellé
     * ni type — et la liste dérivait un peu plus à chaque livraison.
     *
     * La table `fonctionnalites` porte déjà label, description, type et unité :
     * elle devient la source unique. Une clé enregistrée par une migration
     * apparaît désormais avec son vrai libellé, sans toucher au front.
     */
    public function fonctionnalites(): JsonResponse
    {
        $refs = \Illuminate\Support\Facades\DB::table('fonctionnalites')
            ->where('is_actif', true)
            ->orderBy('ordre_affichage')
            ->orderBy('cle')
            ->get(['cle', 'label', 'description', 'type', 'unite', 'categorie', 'ordre_affichage']);

        return response()->json(['fonctionnalites' => $refs]);
    }

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
        $admin = $this->adminUser();

        $data = $request->validate([
            'cle'                          => ['required', 'string', 'max:50', 'unique:niveaux_config,cle'],
            'label'                        => ['required', 'string', 'max:100'],
            'label_en'                     => ['nullable', 'string', 'max:120'],
            'duree_jours'                  => ['required', 'integer', 'min:1'],
            'prix_xof'                     => ['required', 'numeric', 'min:0'],
            'prix_mensuel_equivalent_xof'  => ['nullable', 'numeric', 'min:0'],
            'config'                       => ['required', 'array'],
            'ordre_affichage'              => ['nullable', 'integer'],
            'description_courte'           => ['nullable', 'string', 'max:255'],
            'description_courte_en'        => ['nullable', 'string', 'max:255'],
        ]);

        $data['updated_by'] = $admin->id;
        $data['is_actif']   = true;

        $plan = NiveauConfig::create($data);

        $this->audit($admin, 'plan.create', 'plan', (string) $plan->id, ['cle' => $plan->cle], request()->ip());

        return response()->json($plan, 201);
    }

    public function update(Request $request, NiveauConfig $plan): JsonResponse
    {
        $admin = $this->adminUser();

        $data = $request->validate([
            'label'                        => ['sometimes', 'string', 'max:100'],
            'label_en'                     => ['nullable', 'string', 'max:120'],
            'duree_jours'                  => ['sometimes', 'integer', 'min:1'],
            'prix_xof'                     => ['sometimes', 'numeric', 'min:0'],
            'prix_mensuel_equivalent_xof'  => ['nullable', 'numeric', 'min:0'],
            'config'                       => ['sometimes', 'array'],
            'ordre_affichage'              => ['nullable', 'integer'],
            'description_courte'           => ['nullable', 'string', 'max:255'],
            'description_courte_en'        => ['nullable', 'string', 'max:255'],
        ]);

        $data['updated_by'] = $admin->id;

        $plan->update($data);

        $this->audit($admin, 'plan.update', 'plan', (string) $plan->id, ['cle' => $plan->cle, 'champs' => array_keys($data)], $request->ip());

        return response()->json($plan->fresh());
    }

    public function toggle(Request $request, NiveauConfig $plan): JsonResponse
    {
        $admin = $this->adminUser();

        $plan->update([
            'is_actif'   => ! $plan->is_actif,
            'updated_by' => $admin->id,
        ]);

        $this->audit($admin, $plan->is_actif ? 'plan.activer' : 'plan.desactiver', 'plan', (string) $plan->id, ['cle' => $plan->cle], $request->ip());

        return response()->json(['is_actif' => $plan->is_actif]);
    }
}
