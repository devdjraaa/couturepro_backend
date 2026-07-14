<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\NotificationSysteme;
use App\Models\Proprietaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ResolvesAtelier;
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $notifications = NotificationSysteme::where(function ($q) use ($atelier) {
                $q->where('atelier_id', $atelier->id)
                  ->orWhereNull('atelier_id'); // broadcast global
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'titre'      => $n->titre,
                'contenu'    => $n->contenu,
                'type'       => $n->type,
                'lu'         => $n->is_read,
                'created_at' => $n->created_at,
            ]);

        return response()->json($notifications);
    }

    public function markAsRead(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($request->boolean('all')) {
            NotificationSysteme::where('atelier_id', $atelier->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        } elseif ($request->has('ids')) {
            $ids = (array) $request->input('ids');
            NotificationSysteme::where('atelier_id', $atelier->id)
                ->whereIn('id', $ids)
                ->update(['is_read' => true]);
        }

        return response()->json(['message' => 'Notifications marquées comme lues.']);
    }

    /** Supprime des notifications : { ids: [...] } ou { all: true }. */
    public function destroy(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($request->boolean('all')) {
            NotificationSysteme::where('atelier_id', $atelier->id)->delete();
        } elseif ($request->has('ids')) {
            $ids = (array) $request->input('ids');
            NotificationSysteme::where('atelier_id', $atelier->id)
                ->whereIn('id', $ids)
                ->delete();
        }

        return response()->json(['message' => 'Notifications supprimées.']);
    }

    // #41-42 — Enregistrer le token FCM de l'appareil
    public function registerFcmToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'fcm_token' => ['required', 'string', 'max:512'],
            'platform'  => ['nullable', 'string', 'in:ios,android,web'],
        ]);

        $user = $request->user();
        if ($user instanceof Proprietaire) {
            $user->update([
                'fcm_token'    => $data['fcm_token'],
                'fcm_platform' => $data['platform'] ?? null,
            ]);
        }

        return response()->json(['message' => 'Token FCM enregistré.']);
    }

    // Supprimer le token FCM (déconnexion appareil)
    public function removeFcmToken(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user instanceof Proprietaire) {
            $user->update(['fcm_token' => null, 'fcm_platform' => null]);
        }

        return response()->json(['message' => 'Token FCM supprimé.']);
    }
}
