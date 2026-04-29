<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\NotificationSysteme;
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

}
