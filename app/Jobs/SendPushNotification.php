<?php

namespace App\Jobs;

use App\Services\FcmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Envoi asynchrone d'une notification push FCM (via le worker queue), pour ne
 * pas ralentir la requête qui a créé la notification.
 */
class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public string $token,
        public string $title,
        public string $body,
        public array $data = [],
    ) {}

    public function handle(FcmService $fcm): void
    {
        $fcm->sendToToken($this->token, $this->title, $this->body, $this->data);
    }
}
