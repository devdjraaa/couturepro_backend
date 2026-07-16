<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

// P196 : vérifie un jeton reCAPTCHA v3 côté serveur (ne jamais faire confiance au client).
// No-op si aucune clé secrète n'est configurée → l'inscription reste ouverte tant que
// le chef n'a pas fourni les clés Google.
class VerifyRecaptcha
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('recaptcha.secret');
        if (! $secret) {
            return $next($request); // reCAPTCHA non configuré → on laisse passer
        }

        $token = $request->input('recaptcha_token');
        if (! $token) {
            return response()->json(['message' => 'Vérification anti-robot manquante.'], 422);
        }

        $resp = Http::asForm()->timeout(10)->post(config('recaptcha.verify_url'), [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $request->ip(),
        ]);

        $data = $resp->ok() ? $resp->json() : [];
        $success = (bool) ($data['success'] ?? false);
        $score   = (float) ($data['score'] ?? 0);

        if (! $success || $score < (float) config('recaptcha.min_score')) {
            return response()->json([
                'message' => 'Vérification anti-robot échouée. Réessayez.',
                'code'    => 'recaptcha_failed',
            ], 429);
        }

        return $next($request);
    }
}
