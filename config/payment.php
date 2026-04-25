<?php

return [

    'default_provider' => env('PAYMENT_DEFAULT_PROVIDER', 'fedapay'),

    /*
     * URL du frontend — utilisée pour les redirects utilisateur après paiement.
     * En prod, pointer vers le domaine public de l'app mobile/web.
     */
    'frontend_url' => env('FRONTEND_URL', env('APP_URL')),

    'fedapay' => [
        'api_key'        => env('FEDAPAY_API_KEY'),
        'webhook_secret' => env('FEDAPAY_WEBHOOK_SECRET'),
        'sandbox'        => env('FEDAPAY_SANDBOX', true),

        // Redirect utilisateur après paiement (GET ?status=approved&id=XXX)
        // → pointe vers le frontend, pas l'API
        'return_url' => env(
            'FEDAPAY_RETURN_URL',
            env('FRONTEND_URL', env('APP_URL')) . '/paiement/retour'
        ),

        // URL webhook serveur-à-serveur (POST, à configurer dans le dashboard FedaPay)
        'webhook_url' => env(
            'FEDAPAY_WEBHOOK_URL',
            env('APP_URL') . '/api/webhooks/fedapay'
        ),
    ],

];
