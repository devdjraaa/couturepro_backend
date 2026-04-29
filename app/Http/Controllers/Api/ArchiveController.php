<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Commande;
use App\Models\Mesure;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArchiveController extends Controller
{
    use ResolvesAtelier;

    /**
     * Liste toutes les entités archivées de l'atelier (clients + commandes + mesures).
     * Accessible uniquement par le propriétaire.
     */
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $clients = Client::where('atelier_id', $atelier->id)
            ->where('is_archived', true)
            ->orderByDesc('archived_at')
            ->get()
            ->map(fn ($c) => [
                'entity_type'  => 'client',
                'entity_id'    => $c->id,
                'label'        => "{$c->prenom} {$c->nom}",
                'archived_at'  => $c->archived_at,
                'archive_note' => $c->archive_note,
            ]);

        $commandes = Commande::where('atelier_id', $atelier->id)
            ->where('is_archived', true)
            ->orderByDesc('archived_at')
            ->get()
            ->map(fn ($c) => [
                'entity_type'  => 'commande',
                'entity_id'    => $c->id,
                'label'        => "Commande — {$c->client_nom}",
                'archived_at'  => $c->archived_at,
                'archive_note' => $c->archive_note,
            ]);

        $mesures = Mesure::where('atelier_id', $atelier->id)
            ->where('is_archived', true)
            ->with('client')
            ->orderByDesc('archived_at')
            ->get()
            ->map(fn ($m) => [
                'entity_type'  => 'mesure',
                'entity_id'    => $m->id,
                'label'        => 'Mesures de ' . ($m->client ? "{$m->client->prenom} {$m->client->nom}" : 'client inconnu'),
                'archived_at'  => $m->archived_at,
                'archive_note' => $m->archive_note,
            ]);

        $archives = collect([...$clients, ...$commandes, ...$mesures])
            ->sortByDesc('archived_at')
            ->values();

        return response()->json($archives);
    }
}
