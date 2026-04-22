<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'guard'     => 'web',
        'passwords' => 'proprietaires',
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Guards
    |--------------------------------------------------------------------------
    | web      → guard session standard (non utilisé en mode API)
    | admin    → guard Sanctum pour l'espace Admin
    |
    | NB: Les proprietaires ET equipe_membres utilisent le guard Sanctum
    | par défaut (personal_access_tokens morphable). Le guard 'admin' est
    | séparé pour isoler les sessions admin des sessions proprietaires.
    */

    'guards' => [
        'web' => [
            'driver'   => 'session',
            'provider' => 'proprietaires',
        ],
        'admin' => [
            'driver'   => 'sanctum',
            'provider' => 'admins',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Providers
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'proprietaires' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Proprietaire::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model'  => App\Models\Admin::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    | Couture Pro utilise son propre système OTP/question secrète.
    | Le reset Laravel par défaut n'est pas utilisé.
    */

    'passwords' => [
        'proprietaires' => [
            'provider' => 'proprietaires',
            'table'    => 'password_reset_tokens',
            'expire'   => 60,
            'throttle' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    */

    'password_timeout' => 10800,

];
