<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\NotificationSysteme;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use LogsAdminAction;

    public function store(Request $request): JsonResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'titre'      => ['required', 'string', 'max:150'],
            'contenu'    => ['required', 'string'],
            'type'       => ['required', 'in:info,warning,promo,maintenance'],
            'atelier_id' => ['nullable', 'uuid', 'exists:ateliers,id'],
        ]);

        $isBroadcast = is_null($data['atelier_id'] ?? null);

        if ($isBroadcast) {
            // Créer une notification par atelier actif
            $ateliers = Atelier::where('statut', '!=', 'gele')->get();

            foreach ($ateliers as $atelier) {
                NotificationSysteme::create([
                    'atelier_id' => $atelier->id,
                    'titre'      => $data['titre'],
                    'contenu'    => $data['contenu'],
                    'type'       => $data['type'],
                    'is_read'    => false,
                ]);
            }

            $this->audit($admin, 'notification.broadcast', 'notification', null, [
                'titre'  => $data['titre'],
                'count'  => $ateliers->count(),
            ], $request->ip());

            return response()->json(['message' => "Notification envoyée à {$ateliers->count()} atelier(s)."]);
        }

        $notification = NotificationSysteme::create([
            'atelier_id' => $data['atelier_id'],
            'titre'      => $data['titre'],
            'contenu'    => $data['contenu'],
            'type'       => $data['type'],
            'is_read'    => false,
        ]);

        $this->audit($admin, 'notification.send', 'notification', $notification->id, [
            'titre'      => $data['titre'],
            'atelier_id' => $data['atelier_id'],
        ], $request->ip());

        return response()->json($notification, 201);
    }
}
