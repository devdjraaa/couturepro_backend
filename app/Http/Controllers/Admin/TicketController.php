<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\TicketMessage;
use App\Models\TicketSupport;
use App\Traits\LogsAdminAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use LogsAdminAction;

    public function index(Request $request): JsonResponse
    {
        $tickets = TicketSupport::with(['atelier', 'proprietaire', 'assignedTo'])
            ->when($request->statut, fn($q, $s) => $q->where('statut', $s))
            ->when($request->priorite, fn($q, $p) => $q->where('priorite', $p))
            ->when($request->assigned_to, fn($q, $id) => $q->where('assigned_to', $id))
            ->orderByDesc('created_at')
            ->paginate(25);

        return response()->json($tickets);
    }

    public function show(TicketSupport $ticket): JsonResponse
    {
        $ticket->load(['atelier', 'proprietaire', 'assignedTo', 'messages']);

        // Marquer messages admin non lus comme lus
        $ticket->messages()
            ->where('expediteur_type', 'proprietaire')
            ->whereNull('lu_par_admin_at')
            ->update(['lu_par_admin_at' => now()]);

        return response()->json($ticket);
    }

    public function assigner(Request $request, TicketSupport $ticket): JsonResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'assigned_to' => ['required', 'uuid', 'exists:admins,id'],
        ]);

        $ticket->update(['assigned_to' => $data['assigned_to']]);

        $this->audit($admin, 'ticket.assigner', 'ticket', $ticket->id, [
            'reference'   => $ticket->reference,
            'assigned_to' => $data['assigned_to'],
        ], $request->ip());

        return response()->json(['message' => 'Ticket assigné.']);
    }

    public function repondre(Request $request, TicketSupport $ticket): JsonResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'contenu'         => ['required', 'string', 'max:5000'],
            'is_note_interne' => ['boolean'],
        ]);

        $message = TicketMessage::create([
            'ticket_id'       => $ticket->id,
            'expediteur_type' => 'admin',
            'expediteur_id'   => $admin->id,
            'contenu'         => $data['contenu'],
            'is_note_interne' => $data['is_note_interne'] ?? false,
            'created_at'      => now(),
        ]);

        if ($ticket->statut === 'ouvert' && ! ($data['is_note_interne'] ?? false)) {
            $ticket->update(['statut' => 'en_cours']);
        }

        return response()->json($message, 201);
    }

    public function fermer(Request $request, TicketSupport $ticket): JsonResponse
    {
        $admin = auth('admin')->user();

        if ($ticket->statut === 'ferme') {
            return response()->json(['message' => 'Ticket déjà fermé.'], 422);
        }

        $ticket->update([
            'statut'    => 'ferme',
            'resolu_at' => now(),
        ]);

        $this->audit($admin, 'ticket.fermer', 'ticket', $ticket->id, ['reference' => $ticket->reference], $request->ip());

        return response()->json(['message' => 'Ticket fermé.']);
    }

    public function rouvrir(Request $request, TicketSupport $ticket): JsonResponse
    {
        $admin = auth('admin')->user();

        if ($ticket->statut !== 'ferme') {
            return response()->json(['message' => "Le ticket n'est pas fermé."], 422);
        }

        $ticket->update([
            'statut'    => 'ouvert',
            'resolu_at' => null,
        ]);

        $this->audit($admin, 'ticket.rouvrir', 'ticket', $ticket->id, ['reference' => $ticket->reference], $request->ip());

        return response()->json(['message' => 'Ticket rouvert.']);
    }
}
