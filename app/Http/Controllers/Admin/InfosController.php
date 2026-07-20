<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationSysteme;
use App\Models\VitrineSetting;
use App\Services\InfosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CLI-2 — Diffusion des « Gextimo Infos » depuis le back-office.
 */
class InfosController extends Controller
{
    public function __construct(private InfosService $infos) {}

    /** Clés de catégories acceptées — issues de la config, jamais figées ici. */
    private function categoriesValides(): array
    {
        return array_column(VitrineSetting::categoriesInfos(), 'cle');
    }

    private function regles(): array
    {
        return [
            'titre'          => ['required', 'string', 'max:120'],
            'contenu'        => ['required', 'string', 'max:4000'],
            'categorie'      => ['required', Rule::in($this->categoriesValides())],
            'lien'           => ['present', 'nullable', 'string', 'max:300'],
            'epingle'        => ['required', 'boolean'],
            'publie_at'      => ['present', 'nullable', 'date'],
            'expire_at'      => ['present', 'nullable', 'date', 'after:publie_at'],
            'cible'          => ['required', 'array'],
            'cible.mode'     => ['required', Rule::in(InfosService::MODES)],
            'cible.valeurs'  => ['present', 'array'],
            'cible.valeurs.*' => ['string', 'max:120'],
        ];
    }

    /** GET /admin/infos — historique de diffusion, la plus récente d'abord. */
    public function index(Request $request): JsonResponse
    {
        $items = NotificationSysteme::infos()
            ->orderByDesc('epingle')
            ->orderByDesc('created_at')
            ->paginate(30);

        // La portée est recalculée à l'affichage plutôt que stockée : les
        // ateliers se créent tous les jours, un chiffre figé à l'envoi
        // deviendrait faux le lendemain et induirait la direction en erreur.
        $items->getCollection()->transform(function (NotificationSysteme $i) {
            $i->setAttribute('portee', $this->infos->portee((array) $i->cible));
            $i->setAttribute('lectures', DB::table('infos_lectures')->where('notification_id', $i->id)->count());

            return $i;
        });

        return response()->json([
            'infos'      => $items,
            'categories' => VitrineSetting::categoriesInfos(),
        ]);
    }

    /**
     * POST /admin/infos/portee — combien d'ateliers seraient touchés.
     *
     * Appelé AVANT l'envoi : la direction doit voir le nombre de destinataires
     * avant de diffuser, pas le découvrir après. Une cible mal saisie qui
     * n'atteint personne se voit alors immédiatement.
     */
    public function portee(Request $request): JsonResponse
    {
        $data = $request->validate([
            'mode'      => ['required', Rule::in(InfosService::MODES)],
            'valeurs'   => ['present', 'array'],
            'valeurs.*' => ['string', 'max:120'],
        ]);

        return response()->json(['portee' => $this->infos->portee($data)]);
    }

    /** POST /admin/infos — diffuse une info. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate($this->regles());

        $info = NotificationSysteme::create($data + [
            'canal'      => 'info',
            // Une info est une diffusion : elle n'appartient à aucun atelier.
            // Le ciblage est porté par `cible`, pas par `atelier_id`.
            'atelier_id' => null,
            'type'       => $data['categorie'],
            'is_read'    => false,
        ]);

        return response()->json(['info' => $info], 201);
    }

    /** PUT /admin/infos/{info} — corrige une info déjà diffusée. */
    public function update(Request $request, NotificationSysteme $info): JsonResponse
    {
        if ($info->canal !== 'info') {
            return response()->json(['message' => 'Ce message n\'est pas une info.'], 422);
        }

        $data = $request->validate($this->regles());
        $info->update($data + ['type' => $data['categorie']]);

        return response()->json(['info' => $info->fresh()]);
    }

    /** DELETE /admin/infos/{info} — retire une info. */
    public function destroy(NotificationSysteme $info): JsonResponse
    {
        if ($info->canal !== 'info') {
            return response()->json(['message' => 'Ce message n\'est pas une info.'], 422);
        }

        // Les lectures partent avec (cascade sur la clé étrangère) : garder des
        // lectures orphelines fausserait les statistiques des infos suivantes.
        $info->delete();

        return response()->json(['message' => 'Info supprimée.']);
    }
}
