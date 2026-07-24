<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Models\EquipeMembre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EquipeMembreController extends Controller
{
    use ResolvesAtelier;
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $membres = EquipeMembre::where('atelier_id', $atelier->id)
            ->where('is_active', true)
            ->get(['id', 'nom', 'prenom', 'telephone', 'role', 'code_acces', 'derniere_sync_at', 'created_at']);

        return response()->json($membres);
    }

    /**
     * GET /equipe/roles — ce que chaque rôle peut faire, DANS CET ATELIER.
     *
     * Le patron choisissait un rôle dans une liste sans savoir ce qu'il
     * accordait. Le seul indice affiché — « Création & archivage » pour
     * l'assistant — était même devenu faux : ses droits ont été élargis le
     * 20/07 (modification des clients, commandes et mesures, encaissement),
     * et la mention n'a pas suivi. Une description écrite à la main dérive
     * dès qu'on touche aux permissions.
     *
     * On renvoie donc les droits EFFECTIFS, lus là où ils sont appliqués, et
     * atelier par atelier : un patron peut avoir resserré les siens, et lui
     * montrer les valeurs par défaut lui mentirait.
     *
     * Le refus est aussi utile que l'autorisation — « ne peut pas supprimer »
     * est ce qui décide un patron à confier un rôle plutôt qu'un autre. On
     * renvoie donc les deux listes.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:100'],
            'prenom'    => ['required', 'string', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'role'      => ['required', 'in:assistant,membre'],
        ]);

        $atelier = $this->getAtelier($request);

        $config  = $atelier->abonnement?->getConfigEffective() ?? [];
        $max     = (int) ($config['max_membres'] ?? 0);
        $count   = EquipeMembre::where('atelier_id', $atelier->id)->where('is_active', true)->count();

        if ($max > 0 && $max !== -1 && $count >= $max) {
            return response()->json([
                'message' => "Limite de membres atteinte pour votre plan ({$max} max).",
            ], 403);
        }

        do {
            $code = strtoupper(Str::random(8));
        } while (EquipeMembre::where('code_acces', $code)->exists());

        $membre = EquipeMembre::create([
            'atelier_id' => $atelier->id,
            'created_by' => $request->user()->id,
            'nom'        => $data['nom'],
            'prenom'     => $data['prenom'],
            'telephone'  => $data['telephone'] ?? null,
            'role'       => $data['role'],
            'code_acces' => $code,
            'password'   => bcrypt($code),
            'is_active'  => true,
        ]);

        return response()->json($membre, 201);
    }

    public function destroy(Request $request, EquipeMembre $membre): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($membre->atelier_id !== $atelier->id) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $membre->update([
            'is_active'  => false,
            'revoque_at' => now(),
        ]);

        return response()->json(['message' => 'Membre révoqué.']);
    }

}
