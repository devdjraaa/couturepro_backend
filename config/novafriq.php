<?php

// Réglages transverses NovAfriq/Gextimo (destinataires d'alertes internes).
return [
    // Alerte à chaque nouvelle inscription (P201). Vide = pas d'e-mail.
    'inscription_alert_email' => env('INSCRIPTION_ALERT_EMAIL', env('MAIL_FROM_ADDRESS')),
];
