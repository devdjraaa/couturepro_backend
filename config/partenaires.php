<?php

// Configuration de la fonctionnalité « Partenaires » (P204 / Document Maître Partenaires).
return [
    // Destinataire des alertes internes de candidature (à définir avec la direction).
    // Surchargeable via .env : PARTENAIRES_NOTIFY_EMAIL.
    'notify_email' => env('PARTENAIRES_NOTIFY_EMAIL', 'partenariats@novafriq.africa'),

    // Catégories de partenariat proposées dans le formulaire de candidature.
    // ⚠️ Liste PROVISOIRE : les 12 catégories définitives sont dans le Document Maître
    // Partenaires (Partie 2) — à remplacer ici une fois confirmées, SANS refonte du code.
    // Les partenaires affichés peuvent avoir n'importe quelle catégorie (champ libre) ;
    // cette liste ne sert qu'à pré-remplir le menu déroulant du formulaire.
    'categories' => [
        'institutionnel',
        'financier',
        'logistique',
        'culturel',
        'createur',
        'technologique',
        'media',
        'education',
        'autre',
    ],
];
