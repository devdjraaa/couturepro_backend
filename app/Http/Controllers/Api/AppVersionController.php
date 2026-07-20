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
     *
     * ⚠️ LE BUNDLE DÉPEND DE L'APPLICATION QUI APPELLE. Deux APK sont publiés
     * depuis ce code : l'app des professionnels (com.couturepro.app) et la
     * console interne (com.couturepro.admin). Cette méthode servait le MÊME
     * bundle aux deux — la console téléchargeait donc celui des pros et se
     * faisait remplacer par lui à chaque démarrage (constaté sur appareil le
     * 20/07 : l'APK admin affichait l'écran de connexion des professionnels).
     *
     * Une application non déclarée en configuration ne reçoit AUCUNE OTA. C'est
     * volontairement le cas sûr : mieux vaut une application qui ne se met pas
     * à jour à chaud qu'une application qui en devient une autre.
     */
    public function updates(Request $request): JsonResponse
    {
        $current = (string) $request->input('version_name', '');

        // Capgo transmet l'identifiant du paquet ; selon la version du plugin,
        // sous « app_id » ou « appId ». On accepte les deux plutôt que de
        // dépendre d'une forme qui changerait à la prochaine mise à jour.
        $appId = (string) ($request->input('app_id') ?? $request->input('appId') ?? '');

        // On récupère la table ENTIÈRE puis on l'indexe à la main : `config()`
        // lit la notation à points comme des niveaux imbriqués, or un
        // identifiant de paquet EN CONTIENT — « appversion.ota.com.couturepro.app »
        // irait chercher une clé « com » → « couturepro » → « app » et
        // renverrait toujours null.
        $ota = ((array) config('appversion.ota', []))[$appId] ?? null;

        if (! is_array($ota)) {
            return response()->json((object) []);
        }

        $otaVersion = (string) ($ota['version'] ?? '');
        $otaUrl     = (string) ($ota['url'] ?? '');

        // Aucune OTA publiée pour cette application, ou déjà à jour → rien.
        if ($otaVersion === '' || $otaUrl === '' || $otaVersion === $current) {
            return response()->json((object) []);
        }

        return response()->json([
            'version' => $otaVersion,
            'url'     => $otaUrl,
        ]);
    }
}
