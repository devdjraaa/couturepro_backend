<?php

return [

    // URL publique de la vitrine web (SPA servie par nginx). Sert à construire les
    // URLs canoniques / og:url du rendu OG pour les robots sociaux.
    // Surcharger via VITRINE_URL dans l'environnement de production si le domaine change.
    'url' => env('VITRINE_URL', 'https://gextimo.novafriq.africa'),

    // Image de partage par défaut (si le créateur n'a pas de logo).
    'og_image' => env('VITRINE_OG_IMAGE', env('VITRINE_URL', 'https://gextimo.novafriq.africa') . '/og-cover.png'),

];
