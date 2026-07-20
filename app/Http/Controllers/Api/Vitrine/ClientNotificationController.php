<?php

namespace App\Http\Controllers\Api\Vitrine;

use App\Http\Controllers\Controller;
use App\Models\NotificationClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pt 24 — Notifications du client final, dans son espace.
 *
 * Le client était prévenu par e-mail seulement. Un e-mail se perd, part en
 * indésirable, ou n'est pas relevé — et le client revient sur son espace sans
 * rien y trouver qui lui dise où en est sa commande.
 */
class ClientNotificationController extends Controller
{
    private const PAR_PAGE = 30;

    /** GET /api/vitrine/client/notifications */
    public function index(Request $request): JsonResponse
    {
        $client = $request->user();

        $items = NotificationClient::where('gxt_client_id', $client->id)
            ->ordreAffichage()
            ->limit(self::PAR_PAGE)
            ->get();

        return response()->json([
            'data'     => $items,
            'non_lues' => NotificationClient::where('gxt_client_id', $client->id)->nonLues()->count(),
        ]);
    }

    /** GET /api/vitrine/client/notifications/compteur — pastille de l'onglet. */
    public function compteur(Request $request): JsonResponse
    {
        return response()->json([
            'non_lues' => NotificationClient::where('gxt_client_id', $request->user()->id)->nonLues()->count(),
        ]);
    }

    /** POST /api/vitrine/client/notifications/{notification}/lue */
    public function marquerLue(Request $request, NotificationClient $notification): JsonResponse
    {
        // Le rattachement est vérifié explicitement : sans cela, un client
        // marquerait lues les notifications d'un autre — et confirmerait au
        // passage l'existence d'un identifiant qui ne lui appartient pas.
        if ($notification->gxt_client_id !== $request->user()->id) {
            return response()->json(['message' => 'Notification introuvable.'], 404);
        }

        if (! $notification->lu_at) {
            $notification->update(['lu_at' => now()]);
        }

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    /** POST /api/vitrine/client/notifications/tout-lu */
    public function toutMarquerLu(Request $request): JsonResponse
    {
        NotificationClient::where('gxt_client_id', $request->user()->id)
            ->nonLues()
            ->update(['lu_at' => now()]);

        return response()->json(['message' => 'Toutes les notifications sont marquées comme lues.']);
    }
}
