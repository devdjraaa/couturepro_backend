<?php

namespace App\Http\Controllers\Api\Vitrine;

use App\Http\Controllers\Controller;
use App\Models\GxtClient;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// P202 / Espace Client v3 — Phase 1.
// Authentification du client final de la vitrine, SANS mot de passe :
//   - Google (id_token vérifié côté serveur), ou
//   - E-mail + OTP 6 chiffres (10 min) envoyé par Brevo (réutilise OtpService).
// Émet un jeton Sanctum d'aptitude « gxt-client » (isolé des jetons pro via EnsureAccountType).
class ClientAuthController extends Controller
{
    private const OTP_TYPE = 'client_login';

    public function __construct(private OtpService $otpService) {}

    /** Étape 1 OTP : le client saisit son e-mail (+ WhatsApp) → on envoie un code. */
    public function demanderOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'              => ['required', 'email:rfc', 'max:150'],
            'telephone_whatsapp' => ['nullable', 'string', 'max:25'],
        ]);

        $email = mb_strtolower(trim($data['email']));
        $otp = $this->otpService->generer($email, self::OTP_TYPE, $email);

        return response()->json([
            'message'   => 'Un code de connexion a été envoyé à votre e-mail.',
            'otp_debug' => $this->otpService->debugCode($otp),
        ]);
    }

    /** Étape 2 OTP : le client saisit le code → connexion / création + jeton. */
    public function verifierOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:150'],
            'code'  => ['required', 'string', 'size:6'],
        ]);

        $email = mb_strtolower(trim($data['email']));

        if (! $this->otpService->verifier($email, $data['code'], self::OTP_TYPE)) {
            return response()->json(['message' => 'Code invalide ou expiré.'], 422);
        }

        $client = GxtClient::firstOrNew(['email' => $email]);
        $nouveau = ! $client->exists;
        $client->fill($this->contexte($request));
        $client->telephone_whatsapp = $request->input('telephone_whatsapp') ?: $client->telephone_whatsapp;
        $client->derniere_connexion_at = now();
        $client->save();

        // Lot 2 (20/07) : deux consentements DISTINCTS, enregistrés à la création
        // du compte. La politique est obligatoire côté formulaire ; la newsletter
        // est facultative et n'a aucun effet sur l'accès au service.
        if ($nouveau) {
            if ($request->boolean('privacy_policy_accepted')) {
                $client->accepterPolitique();
            }
            $client->definirNewsletter($request->boolean('newsletter_opt_in'));
        }

        return $this->reponseAuth($client);
    }

    /** Connexion Google : le front envoie l'id_token Google, on le vérifie côté serveur. */
    public function google(Request $request): JsonResponse
    {
        $data = $request->validate(['id_token' => ['required', 'string']]);

        $infos = $this->verifierIdTokenGoogle($data['id_token']);
        if (! $infos) {
            return response()->json(['message' => 'Connexion Google invalide.'], 422);
        }

        $client = GxtClient::firstOrNew(['email' => $infos['email']]);
        $client->fill($this->contexte($request));
        $client->google_id = $infos['sub'];
        $client->nom       = $client->nom ?: ($infos['family_name'] ?? null);
        $client->prenom    = $client->prenom ?: ($infos['given_name'] ?? null);
        $client->derniere_connexion_at = now();
        $client->save();

        return $this->reponseAuth($client);
    }

    public function me(Request $request): JsonResponse
    {
        $client = $request->user();

        return response()->json([
            'client'       => $client,
            'consentement' => $client->dernierConsentement,
        ]);
    }

    /** PATCH /vitrine/client/me — profil basique (brief 16/07 : personnalisation + anniversaire). */
    public function majProfil(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'                => ['nullable', 'string', 'max:100'],
            'prenom'             => ['nullable', 'string', 'max:100'],
            'telephone_whatsapp' => ['nullable', 'string', 'max:25'],
            'ville'              => ['nullable', 'string', 'max:60'],
            'date_naissance'     => ['nullable', 'date', 'before:today'],
            // Lot 2 : réglable à tout moment depuis le compte (exigence explicite).
            'newsletter_opt_in'  => ['nullable', 'boolean'],
        ]);

        $client = $request->user();
        $client->fill(array_filter(
            \Illuminate\Support\Arr::except($data, ['newsletter_opt_in']),
            fn ($v) => $v !== null
        ));
        $client->save();

        if ($request->has('newsletter_opt_in')) {
            $client->definirNewsletter($request->boolean('newsletter_opt_in'));
        }

        return response()->json(['client' => $client]);
    }

    /** Enregistre/actualise le consentement APDP (interrupteur du tracking). */
    public function consentement(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cookie_consent'          => ['required', 'boolean'],
            'marketing_consent'       => ['required', 'boolean'],
            'analytics_consent'       => ['required', 'boolean'],
            'personalization_consent' => ['required', 'boolean'],
            'version_politique'       => ['nullable', 'string', 'max:10'],
        ]);

        $consent = $request->user()->consents()->create([
            ...$data,
            'ip_hash' => hash('sha256', (string) $request->ip()),
        ]);

        return response()->json(['consentement' => $consent], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté.']);
    }

    // ── privé ────────────────────────────────────────────────────────────────

    private function reponseAuth(GxtClient $client): JsonResponse
    {
        $token = $client->createToken('client', ['gxt-client'])->plainTextToken;

        return response()->json([
            'token'        => $token,
            'client'       => $client,
            'consentement' => $client->dernierConsentement,
        ]);
    }

    /** Contexte d'acquisition/technique fourni par le front (uniquement les champs non vides). */
    private function contexte(Request $request): array
    {
        $champs = ['utm_source', 'utm_medium', 'utm_campaign', 'referrer_url',
            'appareil', 'systeme_os', 'navigateur', 'pays', 'ville', 'langue'];

        return collect($request->only($champs))
            ->filter(fn ($v) => filled($v))
            ->map(fn ($v) => is_string($v) ? mb_substr($v, 0, 255) : $v)
            ->all();
    }

    /**
     * Vérifie un id_token Google via l'endpoint tokeninfo et contrôle l'audience
     * (client web OU client Android). Retourne [email, sub, given_name, family_name] ou null.
     */
    private function verifierIdTokenGoogle(string $idToken): ?array
    {
        try {
            $resp = Http::timeout(15)->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);
            if (! $resp->successful()) {
                return null;
            }
            $p = $resp->json();

            $audValides = array_filter([
                config('services.google.client_id'),
                config('services.google.android_client_id'),
            ]);
            if (empty($p['aud']) || ! in_array($p['aud'], $audValides, true)) {
                return null;
            }
            if (empty($p['email']) || ($p['email_verified'] ?? 'false') === 'false') {
                return null;
            }

            return [
                'email'       => mb_strtolower($p['email']),
                'sub'         => $p['sub'] ?? null,
                'given_name'  => $p['given_name'] ?? null,
                'family_name' => $p['family_name'] ?? null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
