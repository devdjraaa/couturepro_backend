<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Proprietaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Laravel\Socialite\Facades\Socialite;

// P150 : connexion sociale (Google / Facebook / Apple) via Laravel Socialite.
// Piloté par la config : un provider n'est actif que si ses clés sont dans .env.
// - email déjà connu  → connexion directe (token renvoyé au front dans le fragment #)
// - nouvel email      → redirection vers l'inscription pré-remplie (téléphone/atelier requis)
class SocialAuthController extends Controller
{
    private array $supported = ['google', 'facebook', 'apple'];

    private function enabled(): array
    {
        return array_values(array_filter(
            $this->supported,
            fn ($p) => filled(config("services.$p.client_id")) && filled(config("services.$p.client_secret"))
        ));
    }

    /** GET /api/auth/social/providers — liste publique des providers actifs (pour afficher les boutons). */
    public function providers(): JsonResponse
    {
        return response()->json([
            'providers' => $this->enabled(),
            // Flux NATIF (app mobile) : le plugin Google a besoin du client ID web
            // (audience du idToken). Servi par l'API → configurable côté serveur,
            // rien de figé dans l'app. (Un client ID n'est pas un secret.)
            'google_web_client_id' => config('services.google.client_id'),
        ]);
    }

    /**
     * POST /api/auth/social/{provider}/token  { id_token } — connexion NATIVE (app mobile).
     * Le plugin natif obtient un idToken Google (Credential Manager) ; on le vérifie auprès
     * de Google puis on applique la même logique que le callback web, en réponse JSON.
     */
    public function tokenLogin(Request $request, string $provider): JsonResponse
    {
        abort_unless($provider === 'google' && in_array('google', $this->enabled(), true), 404);

        $data = $request->validate(['id_token' => ['required', 'string']]);

        // Vérification serveur-à-serveur du idToken (signature + expiration gérées par Google).
        $info = Http::timeout(10)
            ->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $data['id_token']])
            ->json();

        $audiences = array_filter([
            config('services.google.client_id'),
            config('services.google.android_client_id'),
        ]);

        if (! is_array($info)
            || empty($info['email'])
            || ($info['email_verified'] ?? 'false') !== 'true'
            || ! in_array($info['aud'] ?? '', $audiences, true)) {
            return response()->json(['message' => 'Jeton Google invalide.'], 401);
        }

        $proprietaire = Proprietaire::where('email', $info['email'])->first();

        if ($proprietaire) {
            if (! $proprietaire->email_verified_at) {
                $proprietaire->forceFill(['email_verified_at' => now()])->save();
            }

            return response()->json([
                'status' => 'ok',
                'token'  => $proprietaire->createToken('auth_token')->plainTextToken,
            ]);
        }

        // Nouvel utilisateur → le front pré-remplit l'inscription.
        [$prenom, $nom] = $this->splitName((string) ($info['name'] ?? ''));

        return response()->json([
            'status' => 'inscription',
            'email'  => $info['email'],
            'prenom' => $info['given_name'] ?? $prenom,
            'nom'    => $info['family_name'] ?? $nom,
        ]);
    }

    /** GET /api/auth/social/{provider}/redirect */
    public function redirect(string $provider)
    {
        abort_unless(in_array($provider, $this->enabled(), true), 404);

        return Socialite::driver($provider)->stateless()->redirect();
    }

    /** GET /api/auth/social/{provider}/callback */
    public function callback(string $provider)
    {
        $front = rtrim((string) config('payment.frontend_url'), '/');

        abort_unless(in_array($provider, $this->enabled(), true), 404);

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable $e) {
            return redirect($front . '/login?social_error=1');
        }

        $email = $socialUser->getEmail();
        if (! $email) {
            return redirect($front . '/login?social_error=noemail');
        }

        $proprietaire = Proprietaire::where('email', $email)->first();

        if ($proprietaire) {
            // Compte existant → connexion directe. L'email est considéré vérifié.
            if (! $proprietaire->email_verified_at) {
                $proprietaire->forceFill(['email_verified_at' => now()])->save();
            }
            $token = $proprietaire->createToken('auth_token')->plainTextToken;

            // Fragment (#) : le token n'est pas transmis au serveur ni journalisé.
            return redirect($front . '/auth/social/callback#token=' . urlencode($token));
        }

        // Nouvel utilisateur : on pré-remplit l'inscription (téléphone, atelier, question
        // secrète et mot de passe restent obligatoires — non fournis par le provider).
        [$prenom, $nom] = $this->splitName((string) $socialUser->getName());
        $params = http_build_query([
            'social' => $provider,
            'email'  => $email,
            'prenom' => $prenom,
            'nom'    => $nom,
        ]);

        return redirect($front . '/register?' . $params);
    }

    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        if (count($parts) <= 1) {
            return [$name, ''];
        }
        $prenom = array_shift($parts);

        return [$prenom, implode(' ', $parts)];
    }
}
