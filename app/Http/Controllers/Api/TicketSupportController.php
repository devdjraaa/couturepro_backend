<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Atelier;
use App\Models\EquipeMembre;
use App\Models\Proprietaire;
use App\Models\TicketMessage;
use App\Models\TicketSupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketSupportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $tickets = TicketSupport::where('atelier_id', $atelier->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'reference'  => $t->reference,
                'sujet'      => $t->sujet,
                'categorie'  => $t->categorie,
                'statut'     => $t->statut,
                'priorite'   => $t->priorite,
                'created_at' => $t->created_at,
            ]);

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sujet'     => ['required', 'string', 'max:255'],
            'message'   => ['required', 'string', 'max:5000'],
            'categorie' => ['required', 'in:facturation,technique,compte,abonnement,autre'],
            'photo'     => ['nullable', 'image', 'max:5120'],
        ]);

        $atelier = $this->getAtelier($request);
        $user    = $request->user();
        $propId  = $user instanceof Proprietaire
            ? $user->id
            : Atelier::find($user->atelier_id)?->proprietaire_id;

        $ticket = TicketSupport::create([
            'reference'       => TicketSupport::genererReference(),
            'atelier_id'      => $atelier->id,
            'proprietaire_id' => $propId,
            'sujet'           => $data['sujet'],
            'categorie'       => $data['categorie'],
            'statut'          => 'ouvert',
            'priorite'        => 'normale',
        ]);

        $pjPath = null;
        if ($request->hasFile('photo')) {
            $pjPath = $request->file('photo')->store('tickets', 'public');
        }

        TicketMessage::create([
            'ticket_id'       => $ticket->id,
            'expediteur_type' => 'proprietaire',
            'expediteur_id'   => $propId,
            'contenu'         => $data['message'],
            'pj_path'         => $pjPath,
            'created_at'      => now(),
        ]);

        return response()->json([
            'id'         => $ticket->id,
            'reference'  => $ticket->reference,
            'sujet'      => $ticket->sujet,
            'categorie'  => $ticket->categorie,
            'statut'     => $ticket->statut,
            'created_at' => $ticket->created_at,
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $ticket  = TicketSupport::where('atelier_id', $atelier->id)->findOrFail($id);

        $messages = TicketMessage::where('ticket_id', $ticket->id)
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => [
                'id'              => $m->id,
                'expediteur_type' => $m->expediteur_type,
                'contenu'         => $m->contenu,
                'pj_url'          => $m->pj_path ? asset('storage/' . $m->pj_path) : null,
                'created_at'      => $m->created_at,
            ]);

        return response()->json([
            'id'         => $ticket->id,
            'reference'  => $ticket->reference,
            'sujet'      => $ticket->sujet,
            'categorie'  => $ticket->categorie,
            'statut'     => $ticket->statut,
            'priorite'   => $ticket->priorite,
            'created_at' => $ticket->created_at,
            'messages'   => $messages,
        ]);
    }

    public function repondre(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:5000'],
            'photo'   => ['nullable', 'image', 'max:5120'],
        ]);

        $atelier = $this->getAtelier($request);
        $ticket  = TicketSupport::where('atelier_id', $atelier->id)->findOrFail($id);

        $user   = $request->user();
        $propId = $user instanceof Proprietaire
            ? $user->id
            : Atelier::find($user->atelier_id)?->proprietaire_id;

        $pjPath = null;
        if ($request->hasFile('photo')) {
            $pjPath = $request->file('photo')->store('tickets', 'public');
        }

        $message = TicketMessage::create([
            'ticket_id'       => $ticket->id,
            'expediteur_type' => 'proprietaire',
            'expediteur_id'   => $propId,
            'contenu'         => $data['message'],
            'pj_path'         => $pjPath,
            'created_at'      => now(),
        ]);

        return response()->json([
            'id'              => $message->id,
            'expediteur_type' => $message->expediteur_type,
            'contenu'         => $message->contenu,
            'pj_url'          => $pjPath ? asset('storage/' . $pjPath) : null,
            'created_at'      => $message->created_at,
        ], 201);
    }

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();
        return $user instanceof EquipeMembre ? $user->atelier : $user->atelierMaitre;
    }
}
