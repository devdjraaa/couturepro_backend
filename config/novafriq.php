<?php

// Réglages transverses NovAfriq/Gextimo (destinataires d'alertes internes).
return [
    // Alerte à chaque nouvelle inscription (P201). Vide = pas d'e-mail.
    // Défaut = direction@ (adresse réelle qui redirige vers le Gmail du boss) — surtout
    // PAS MAIL_FROM_ADDRESS, qui est un « noreply » que personne ne relève.
    'inscription_alert_email' => env('INSCRIPTION_ALERT_EMAIL', 'direction@novafriq.africa'),
];
