<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contrôle de version de l'application mobile
    |--------------------------------------------------------------------------
    | Sert de « videur » : l'app compare sa version native à ces valeurs au
    | démarrage (GET /api/app/version).
    |  - version < min_version  → mise à jour OBLIGATOIRE (télécharger l'APK)
    |  - version < latest_version → mise à jour recommandée (facultative)
    */
    'min_version'    => env('APP_MIN_VERSION', '1.0'),
    'latest_version' => env('APP_LATEST_VERSION', '1.0'),
    'apk_url'        => env('APP_APK_URL', 'https://gextimo.novafriq.africa/Gextimo-v1.0.apk'),
    'note'           => env('APP_UPDATE_NOTE', ''),

    /*
    |--------------------------------------------------------------------------
    | OTA — mises à jour « à chaud » du bundle web (self-hosted Capgo)
    |--------------------------------------------------------------------------
    | Tant que 'version' et 'url' sont vides, AUCUNE OTA n'est proposée
    | (comportement sûr). On les renseigne (via .env) au moment de publier
    | un nouveau bundle web.
    */
    'ota' => [
        'version' => env('APP_OTA_VERSION', ''),
        'url'      => env('APP_OTA_URL', ''),
    ],
];
