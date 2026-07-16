<?php

// P196 : protection anti-robot (reCAPTCHA v3) — inactif tant que les clés ne sont
// pas fournies (le middleware laisse passer). S'active dès que RECAPTCHA_SECRET est mis.
return [
    'site_key'  => env('RECAPTCHA_SITE_KEY'),
    'secret'    => env('RECAPTCHA_SECRET'),
    'min_score' => (float) env('RECAPTCHA_MIN_SCORE', 0.5),
    'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
];
