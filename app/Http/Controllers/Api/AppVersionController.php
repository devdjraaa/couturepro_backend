<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    /**
     * GET /api/app/version
     * Le « videur » : l'app compare sa version native à ces valeurs.
     */
    public function version(): JsonResponse
    {
        return response()->json([
            'min_version'    => (string) config('appversion.min_version'),
            'latest_version' => (string) config('appversion.latest_version'),
            'apk_url'        => (string) config('appversion.apk_url'),
            'note'           => (string) config('appversion.note'),
        ]);
    }

    /**
     * POST /api/app/updates
     * Endpoint self-hosted pour @capgo/capacitor-updater (OTA du bundle web).
     * Capgo envoie la version courante du bundle ; on renvoie le dernier bundle
     * s'il est plus récent, sinon un objet vide (= « pas de mise à jour »).
     */
    public function updates(Request $request): JsonResponse
    {
        $current    = (string) $request->input('version_name', '');
        $otaVersion = (string) config('appversion.ota.version');
        $otaUrl     = (string) config('appversion.ota.url');

        // Aucune OTA publiée, ou l'app est déjà à jour → rien à faire (sûr).
        if ($otaVersion === '' || $otaUrl === '' || $otaVersion === $current) {
            return response()->json((object) []);
        }

        return response()->json([
            'version' => $otaVersion,
            'url'     => $otaUrl,
        ]);
    }
}
