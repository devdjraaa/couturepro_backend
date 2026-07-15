<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patron;
use App\Models\Vetement;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

// P161-163 (côté créateur) : gestion des patrons payants attachés à ses créations.
// Réservé aux comptes designer dont le plan inclut la vente de contenus numériques.
class PatronController extends Controller
{
    use ResolvesAtelier;

    private function assertPeutVendre($atelier): void
    {
        // Seuls les designers ont une vitrine publique ; la capacité est pilotée par le plan
        // (config `patrons_payants`, incluse par défaut dans les offres premium).
        abort_unless($atelier->type === 'designer', 403, "La vente de patrons est réservée aux comptes créateur.");
        $inclus = $atelier->abonnement?->getConfigEffective()['patrons_payants'] ?? true;
        abort_unless($inclus, 403, "La vente de patrons n'est pas incluse dans votre offre.");
    }

    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $patrons = Patron::where('atelier_id', $atelier->id)
            ->withCount(['achats as ventes' => fn ($q) => $q->where('statut', 'paye')])
            ->latest()
            ->get();

        return response()->json($patrons);
    }

    public function store(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $this->assertPeutVendre($atelier);

        $data = $request->validate([
            'vetement_id' => ['required', 'uuid'],
            'titre'       => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'prix'        => ['required', 'integer', 'min:100'],
            'fichier'     => ['required', 'file', 'mimes:pdf,zip,png,jpg,jpeg', 'max:20480'], // 20 Mo
        ]);

        // La création doit appartenir à un de mes ateliers.
        $atelierIds = $this->ateliersAutorises($request);
        abort_unless(
            Vetement::where('id', $data['vetement_id'])->whereIn('atelier_id', $atelierIds)->exists(),
            422,
            'Création introuvable pour vos ateliers.'
        );

        $file = $request->file('fichier');
        $path = $file->store('patrons'); // disque local privé (jamais accessible publiquement)

        $patron = Patron::create([
            'atelier_id'    => $atelier->id,
            'vetement_id'   => $data['vetement_id'],
            'titre'         => $data['titre'],
            'description'   => $data['description'] ?? null,
            'prix'          => $data['prix'],
            'fichier_path'  => $path,
            'fichier_nom'   => $file->getClientOriginalName(),
            'fichier_taille' => $file->getSize(),
            'actif'         => true,
        ]);

        return response()->json($patron, 201);
    }

    public function update(Request $request, Patron $patron): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        abort_unless($patron->atelier_id === $atelier->id, 403);

        $data = $request->validate([
            'titre'       => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'prix'        => ['sometimes', 'integer', 'min:100'],
            'actif'       => ['sometimes', 'boolean'],
            'fichier'     => ['sometimes', 'file', 'mimes:pdf,zip,png,jpg,jpeg', 'max:20480'],
        ]);

        if ($request->hasFile('fichier')) {
            Storage::delete($patron->fichier_path);
            $file = $request->file('fichier');
            $data['fichier_path']   = $file->store('patrons');
            $data['fichier_nom']    = $file->getClientOriginalName();
            $data['fichier_taille'] = $file->getSize();
        }

        $patron->update($data);

        return response()->json($patron->fresh());
    }

    public function destroy(Request $request, Patron $patron): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        abort_unless($patron->atelier_id === $atelier->id, 403);

        // On garde le fichier tant que des achats payés existent (droit de re-téléchargement).
        if (!$patron->achats()->where('statut', 'paye')->exists()) {
            Storage::delete($patron->fichier_path);
            $patron->delete();
        } else {
            $patron->update(['actif' => false]); // retiré de la vente, téléchargements préservés
        }

        return response()->json(['message' => 'Patron retiré.']);
    }
}
