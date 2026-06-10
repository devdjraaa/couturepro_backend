<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ResolvesAtelier;
use App\Http\Requests\Api\StoreMesureRequest;
use App\Models\Atelier;
use App\Models\Client;
use App\Models\EquipeMembre;
use App\Models\Mesure;
use App\Models\NotificationSysteme;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MesureController extends Controller
{
    use ResolvesAtelier;
    use AuthorizesRequests;

    public function index(Request $request, string $clientId): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $mesure = Mesure::where('atelier_id', $atelier->id)
            ->where('client_id', $clientId)
            ->first();

        return response()->json($mesure);
    }

    public function store(StoreMesureRequest $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $user    = $request->user();

        $mesure = Mesure::updateOrCreate(
            ['atelier_id' => $atelier->id, 'client_id' => $request->client_id],
            [
                'champs'          => $request->champs,
                'created_by'      => $user->id,
                'created_by_role' => $user instanceof EquipeMembre ? $user->role : 'proprietaire',
            ]
        );

        return response()->json($mesure, 201);
    }

    public function update(Request $request, Mesure $mesure): JsonResponse
    {
        $this->authorize('update', $mesure);

        $mesure->update(['champs' => $request->validate(['champs' => ['required', 'array']])['champs']]);

        return response()->json($mesure);
    }

    public function archiver(Request $request, Mesure $mesure): JsonResponse
    {
        $this->authorize('archive', $mesure);

        $note   = $request->input('note');
        $auteur = $request->user();
        $nom    = $auteur->prenom ?? $auteur->nom ?? 'Un assistant';
        $client = $mesure->client;

        $mesure->update([
            'is_archived'  => true,
            'archived_at'  => now(),
            'archived_by'  => $auteur->id,
            'archive_note' => $note,
        ]);

        $atelier = $this->getAtelier($request);

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => "Mesures archivées par {$nom}",
            'contenu'    => ($client ? "{$client->prenom} {$client->nom}" : 'Client inconnu') . ($note ? " — {$note}" : ''),
            'type'       => 'alerte_archive',
            'is_read'    => false,
        ]);

        return response()->json(['message' => 'Mesures archivées.']);
    }

    public function desarchiver(Request $request, Mesure $mesure): JsonResponse
    {
        $this->authorize('update', $mesure);

        $mesure->update([
            'is_archived'  => false,
            'archived_at'  => null,
            'archived_by'  => null,
            'archive_note' => null,
        ]);

        return response()->json(['message' => 'Mesures désarchivées.']);
    }

    public function destroy(Request $request, Mesure $mesure): JsonResponse
    {
        $this->authorize('delete', $mesure);

        $mesure->delete();

        return response()->json(['message' => 'Mesure supprimée.']);
    }

    // #9-10, #59-61 — Export mesures CSV
    public function exportCsv(Request $request, string $clientId): StreamedResponse
    {
        $atelier = $this->getAtelier($request);
        $client  = Client::where('id', $clientId)->where('atelier_id', $atelier->id)->firstOrFail();
        $mesure  = Mesure::where('atelier_id', $atelier->id)->where('client_id', $clientId)->first();

        $nom     = trim("{$client->prenom} {$client->nom}");
        $champs  = $mesure?->champs ?? [];

        $filename = 'mesures_' . str_replace(' ', '_', strtolower($nom)) . '_' . now()->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($nom, $champs, $atelier) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
            fputcsv($out, ['Atelier', 'Client', 'Mesure', 'Valeur', 'Date export']);
            foreach ($champs as $cle => $valeur) {
                fputcsv($out, [
                    $atelier->nom,
                    $nom,
                    ucfirst(str_replace('_', ' ', $cle)),
                    $valeur,
                    now()->format('d/m/Y'),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // #9-10 — Lien WhatsApp avec les mesures formatées en texte
    public function exportWhatsApp(Request $request, string $clientId): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $client  = Client::where('id', $clientId)->where('atelier_id', $atelier->id)->firstOrFail();
        $mesure  = Mesure::where('atelier_id', $atelier->id)->where('client_id', $clientId)->first();

        if (!$client->telephone) {
            return response()->json(['message' => 'Ce client n\'a pas de numéro de téléphone.'], 422);
        }

        $nom    = trim("{$client->prenom} {$client->nom}");
        $champs = $mesure?->champs ?? [];

        $lignes = ["📏 *Mesures de {$nom}* — {$atelier->nom}", ''];
        foreach ($champs as $cle => $valeur) {
            $lignes[] = ucfirst(str_replace('_', ' ', $cle)) . ' : ' . $valeur;
        }
        $lignes[] = '';
        $lignes[] = '_Exporté le ' . now()->format('d/m/Y') . '_';

        $message = implode("\n", $lignes);
        $phone   = preg_replace('/\D/', '', $client->telephone);
        $lien    = 'https://wa.me/' . $phone . '?text=' . rawurlencode($message);

        return response()->json(['lien' => $lien, 'message' => $message]);
    }
}
