<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fcm' => [
        // API HTTP v1 : chemin du JSON de compte de service (hors git) + ID projet Firebase.
        'credentials' => env('FCM_CREDENTIALS', storage_path('app/firebase/service-account.json')),
        'project_id'  => env('FCM_PROJECT_ID', 'gextimo-28a49'),
    ],

    // P200 : veille SEO hebdo — destinataire du rapport (vide = fichier/log seulement)
    // + clé API PageSpeed Insights (gratuite, Google Cloud Console, sinon quota anonyme 429)
    'veille_seo' => [
        'email'   => env('VEILLE_SEO_EMAIL'),
        'psi_key' => env('PSI_API_KEY'),
    ],

    // P150 : connexion sociale (Laravel Socialite). Chaque provider n'est ACTIF que si
    // ses client_id + client_secret sont renseignés dans .env → le jour où le boss a les
    // clés, il les colle ici (via .env) + `config:cache` et les boutons s'activent seuls.
    // redirect par défaut = callback backend {APP_URL}/api/auth/social/{provider}/callback.
    'google' => [
        'client_id'     => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect'      => env('GOOGLE_REDIRECT_URI', env('APP_URL') . '/api/auth/social/google/callback'),
        // Client OAuth « Android » (flux natif Credential Manager) : sert à valider
        // l'audience des idToken émis pour l'app mobile.
        'android_client_id' => env('GOOGLE_ANDROID_CLIENT_ID'),
    ],

    'facebook' => [
        'client_id'     => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect'      => env('FACEBOOK_REDIRECT_URI', env('APP_URL') . '/api/auth/social/facebook/callback'),
    ],

    // Apple : client_id = Services ID ; client_secret = JWT généré (clé .p8 + team_id + key_id).
    'apple' => [
        'client_id'     => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'redirect'      => env('APPLE_REDIRECT_URI', env('APP_URL') . '/api/auth/social/apple/callback'),
    ],

];
