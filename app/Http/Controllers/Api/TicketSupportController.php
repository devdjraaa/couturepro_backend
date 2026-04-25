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

        TicketMessage::create([
            'ticket_id'       => $ticket->id,
            'expediteur_type' => 'proprietaire',
            'expediteur_id'   => $propId,
            'contenu'         => $data['message'],
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

    private function getAtelier(Request $request): Atelier
    {
        $user = $request->user();
        return $user instanceof EquipeMembre ? $user->atelier : $user->atelierMaitre;
    }
}
