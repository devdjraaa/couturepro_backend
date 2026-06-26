<?php

return [

    /*
    |--------------------------------------------------------------------------
    | URL de base de l'API e-MECeF (DGI Bénin)
    |--------------------------------------------------------------------------
    |
    | Par DÉFAUT : environnement de TEST. Chaque appel à la production crée une
    | vraie facture fiscale (compteur, quasi irréversible) — on ne bascule sur
    | la prod que volontairement, via EMECEF_BASE_URL dans le .env :
    |   Test : https://developper.impots.bj/sygmef-emcf/api
    |   Prod : https://sygmef.impots.bj/emcf
    |
    */
    'base_url' => env('EMECEF_BASE_URL', 'https://developper.impots.bj/sygmef-emcf/api'),

    // Délai max (s) des appels HTTP à l'API e-MECeF.
    'timeout' => (int) env('EMECEF_TIMEOUT', 20),

];
