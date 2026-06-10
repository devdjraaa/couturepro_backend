<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    private string $serverKey;
    private string $endpoint = 'https://fcm.googleapis.com/fcm/send';

    public function __construct()
    {
        $this->serverKey = config('services.fcm.server_key', '');
    }

    public function sendToToken(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (empty($this->serverKey) || empty($fcmToken)) {
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type'  => 'application/json',
            ])->post($this->endpoint, [
                'to'           => $fcmToken,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                    'badge' => 1,
                ],
                'data'         => $data,
                'priority'     => 'high',
            ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::warning('FCM send failed: ' . $e->getMessage());
            return false;
        }
    }
}
