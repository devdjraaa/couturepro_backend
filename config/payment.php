<?php

return [

    'default_provider' => env('PAYMENT_DEFAULT_PROVIDER', 'fedapay'),

    'fedapay' => [
        'api_key'        => env('FEDAPAY_API_KEY'),
        'webhook_secret' => env('FEDAPAY_WEBHOOK_SECRET'),
        'sandbox'        => env('FEDAPAY_SANDBOX', true),
        'callback_url'   => env('FEDAPAY_CALLBACK_URL', env('APP_URL') . '/api/webhooks/fedapay'),
    ],

];
