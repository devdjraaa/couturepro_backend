<?php

namespace App\Observers;

use App\Jobs\SendPushNotification;
use App\Models\NotificationSysteme;
use Illuminate\Support\Facades\Log;

/**
 * À chaque notification système créée, envoie une push FCM au propriétaire de
 * l'atelier concerné (si un token FCM est enregistré). Centralisé ici → toutes
 * les notifs (commande, devis, abonnement, fidélité…) déclenchent une push.
 */
class NotificationSystemeObserver
{
    public function created(NotificationSysteme $notif): void
    {
        try {
            $token = $notif->atelier?->proprietaire?->fcm_token;
            if (! $token) {
                return;
            }

            SendPushNotification::dispatch(
                $token,
                $notif->titre ?: 'Gextimo',
                (string) $notif->contenu,
                ['lien' => (string) ($notif->lien ?? ''), 'type' => (string) ($notif->type ?? '')],
            );
        } catch (\Throwable $e) {
            Log::warning('NotificationSystemeObserver: '.$e->getMessage());
        }
    }
}
