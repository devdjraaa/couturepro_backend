<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envoi de notifications push via l'API FCM HTTP v1 (l'API « legacy » server_key
 * a été fermée par Google en juin 2024). L'authentification se fait avec un
 * compte de service (JWT signé RS256 → jeton OAuth2), sans dépendance externe.
 *
 * Config (config/services.php → 'fcm') :
 *   'credentials' : chemin du JSON de compte de service (hors git)
 *   'project_id'  : ID du projet Firebase
 */
class FcmService
{
    private ?array $sa = null;
    private string $projectId = '';

    public function __construct()
    {
        $path = (string) config('services.fcm.credentials', '');
        if ($path && is_file($path)) {
            $this->sa = json_decode((string) file_get_contents($path), true) ?: null;
        }
        $this->projectId = (string) config('services.fcm.project_id', $this->sa['project_id'] ?? '');
    }

    public function isConfigured(): bool
    {
        return $this->sa !== null && $this->projectId !== '';
    }

    /** Envoie une push à un token. Retourne false si non configuré / échec / token invalide. */
    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (! $this->isConfigured() || $fcmToken === '') {
            return false;
        }

        $access = $this->accessToken();
        if (! $access) {
            return false;
        }

        // L'API v1 exige des data string => string.
        $strData = [];
        foreach ($data as $k => $v) {
            if ($v !== null) {
                $strData[(string) $k] = (string) $v;
            }
        }

        try {
            $resp = Http::withToken($access)
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                    'message' => [
                        'token'        => $fcmToken,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data'         => $strData,
                        'android'      => [
                            'priority'     => 'HIGH',
                            'notification' => [
                                'sound'                   => 'default',
                                'default_vibrate_timings' => true,
                                'notification_priority'   => 'PRIORITY_MAX',
                            ],
                        ],
                    ],
                ]);

            if (! $resp->successful()) {
                Log::warning('FCM v1 non-2xx', ['status' => $resp->status(), 'body' => $resp->body()]);
            }

            return $resp->successful();
        } catch (\Throwable $e) {
            Log::warning('FCM v1 send failed: '.$e->getMessage());

            return false;
        }
    }

    /** Jeton OAuth2 (mis en cache ~55 min) obtenu par JWT signé avec le compte de service. */
    private function accessToken(): ?string
    {
        $cached = Cache::get('fcm_access_token');
        if ($cached) {
            return $cached;
        }

        try {
            $now = time();
            $header = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->b64url(json_encode([
                'iss'   => $this->sa['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud'   => 'https://oauth2.googleapis.com/token',
                'iat'   => $now,
                'exp'   => $now + 3600,
            ]));
            $signingInput = $header.'.'.$claims;

            $signature = '';
            openssl_sign($signingInput, $signature, $this->sa['private_key'], OPENSSL_ALGO_SHA256);
            $assertion = $signingInput.'.'.$this->b64url($signature);

            $resp = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $assertion,
            ]);

            if (! $resp->successful()) {
                Log::warning('FCM OAuth token failed', ['body' => $resp->body()]);

                return null;
            }

            $access = $resp->json('access_token');
            if ($access) {
                Cache::put('fcm_access_token', $access, 3300);
            }

            return $access;
        } catch (\Throwable $e) {
            Log::warning('FCM OAuth exception: '.$e->getMessage());

            return null;
        }
    }

    private function b64url(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }
}
