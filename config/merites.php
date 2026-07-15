<?php

// P174-176 : système de mérites du profil créateur. 6 catégories, 5 niveaux chacune.
// Les seuils/noms viennent de la proposition P175 du cahier de retours (⚠️ À VALIDER par
// le boss). Zéro hardcoding : tout est éditable ici, la logique de calcul (MeritesService)
// ne fait que lire cette config.
//
// NB P175 : la proposition dupliquait « Rayonnant » pour les Vues niveaux 3 ET 5.
// Choix appliqué ici : niveau 3 = « Rayonnant », niveau 5 = « Éclatant » (à confirmer).
//
// Chaque niveau : min = seuil d'entrée (compteur >= min). Le dernier niveau n'a pas de max.

return [

    'categories' => [

        'likes' => [
            'emoji'   => '❤️',
            'label'   => "J'aime",
            'source'  => 'likes',       // total de likes reçus sur les créations
            'niveaux' => [
                ['niveau' => 1, 'min' => 0,    'nom' => 'Nouveau venu'],
                ['niveau' => 2, 'min' => 101,  'nom' => 'Apprécié'],
                ['niveau' => 3, 'min' => 501,  'nom' => 'Populaire'],
                ['niveau' => 4, 'min' => 2001, 'nom' => 'Inspirant'],
                ['niveau' => 5, 'min' => 5001, 'nom' => 'Référence'],
            ],
        ],

        'avis' => [
            'emoji'   => '💬',
            'label'   => 'Avis',
            'source'  => 'avis',
            'niveaux' => [
                ['niveau' => 1, 'min' => 0,    'nom' => 'Découvert'],
                ['niveau' => 2, 'min' => 51,   'nom' => 'Remarqué'],
                ['niveau' => 3, 'min' => 201,  'nom' => 'Reconnu'],
                ['niveau' => 4, 'min' => 501,  'nom' => 'Influent'],
                ['niveau' => 5, 'min' => 1001, 'nom' => 'Incontournable'],
            ],
        ],

        'telechargements' => [
            'emoji'   => '📥',
            'label'   => 'Téléchargements',
            'source'  => 'telechargements', // patrons payants (Phase 2) — 0 tant qu'inactif
            'niveaux' => [
                ['niveau' => 1, 'min' => 0,    'nom' => 'Partagé'],
                ['niveau' => 2, 'min' => 51,   'nom' => 'Diffusé'],
                ['niveau' => 3, 'min' => 201,  'nom' => 'Adopté'],
                ['niveau' => 4, 'min' => 501,  'nom' => 'Plébiscité'],
                ['niveau' => 5, 'min' => 1001, 'nom' => 'Inégalé'],
            ],
        ],

        'commandes' => [
            'emoji'   => '🛒',
            'label'   => 'Commandes',
            'source'  => 'commandes',
            'niveaux' => [
                ['niveau' => 1, 'min' => 0,   'nom' => 'Premier pas'],
                ['niveau' => 2, 'min' => 21,  'nom' => 'En activité'],
                ['niveau' => 3, 'min' => 101, 'nom' => 'Productif'],
                ['niveau' => 4, 'min' => 301, 'nom' => 'Prolifique'],
                ['niveau' => 5, 'min' => 501, 'nom' => 'Expert'],
            ],
        ],

        'vues' => [
            'emoji'   => '👁️',
            'label'   => 'Vues',
            'source'  => 'vues',
            'niveaux' => [
                ['niveau' => 1, 'min' => 0,     'nom' => 'Visible'],
                ['niveau' => 2, 'min' => 501,   'nom' => 'Remarqué'],
                ['niveau' => 3, 'min' => 2001,  'nom' => 'Rayonnant'],
                ['niveau' => 4, 'min' => 10001, 'nom' => 'Vibrant'],
                ['niveau' => 5, 'min' => 50001, 'nom' => 'Éclatant'], // P175 : était « Rayonnant » (doublon) → à confirmer
            ],
        ],

        'anciennete' => [
            'emoji'   => '🕐',
            'label'   => 'Ancienneté',
            'source'  => 'anciennete_mois', // nombre de mois depuis l'inscription
            'niveaux' => [
                ['niveau' => 1, 'min' => 0,  'nom' => 'Novice'],
                ['niveau' => 2, 'min' => 3,  'nom' => 'Confirmé'],
                ['niveau' => 3, 'min' => 6,  'nom' => 'Expérimenté'],
                ['niveau' => 4, 'min' => 12, 'nom' => 'Vétéran'],
                ['niveau' => 5, 'min' => 24, 'nom' => 'Pionnier'],
            ],
        ],

    ],
];
