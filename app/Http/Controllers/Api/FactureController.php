<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use App\Models\ParametresAtelier;
use App\Services\EMecefService;
use App\Traits\ChecksPlanFeature;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FactureController extends Controller
{
    use ResolvesAtelier, ChecksPlanFeature;

    // GET /api/factures — documents (devis/factures/reçus) de mon atelier.
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        return response()->json(
            Facture::where('atelier_id', $atelier->id)->latest()->get()
        );
    }

    // GET /api/factures/{facture}
    public function show(Request $request, Facture $facture): JsonResponse
    {
        $this->authorizeFacture($request, $facture);

        return response()->json($facture);
    }

    // POST /api/factures — crée un devis / une facture / un reçu.
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'                   => ['required', 'in:devis,facture,recu'],
            'client_nom'             => ['required', 'string', 'max:120'],
            'client_telephone'       => ['nullable', 'string', 'max:40'],
            'date_echeance'          => ['nullable', 'date'],
            'lignes'                 => ['nullable', 'array'], // facture importée (PDF déjà complet) = pas de lignes
            'lignes.*.description'   => ['required', 'string', 'max:255'],
            'lignes.*.quantite'      => ['required', 'numeric', 'min:0'],
            'lignes.*.prix_unitaire' => ['required', 'numeric', 'min:0'],
            // S08C-30 : le mode de paiement doit appartenir à la liste configurée
            // (le champ acceptait auparavant N'IMPORTE QUELLE chaîne).
            //
            // ⚠️ TRANSITION : les anciennes valeurs restent acceptées tant que le front
            // déployé les envoie (il a « wave » par défaut). Sans cette tolérance, la
            // création de factures casserait en production dès ce déploiement.
            // → retirer self::MODES_HERITES une fois SUIVI_FRONTEND.md#S08C-30 livré.
            'mode_paiement'          => ['nullable', 'string', 'max:30', Rule::in($this->modesAutorises())],
            'gabarit'                => ['nullable', 'string', 'max:30'],
            'acompte'                => ['nullable', 'numeric', 'min:0'],
            'notes'                  => ['nullable', 'string', 'max:1000'],
        ]);

        $atelier = $this->getAtelier($request);
        if ($gate = $this->planGate($atelier, 'facturation')) {
            return $gate;
        }

        // Plan gratuit : 10 clients DIFFÉRENTS facturés par période (les factures d'un
        // client déjà compté restent illimitées ; devis exclus) — logique direction 16/07/2026.
        $refus = app(\App\Services\AtelierLimitsService::class)
            ->factureRefus($atelier, $data['type'], $data['client_nom'], $data['client_telephone'] ?? null);
        if ($refus) {
            return response()->json(['message' => $refus, 'feature_bloquee' => 'clients_factures_periode'], 403);
        }

        $prefs = ParametresAtelier::firstOrNew(['atelier_id' => $atelier->id]);

        $facture = Facture::create([
            'atelier_id'       => $atelier->id,
            'numero'           => $this->genererNumero($atelier->id, $data['type']),
            'type'             => $data['type'],
            'statut'           => 'non_payee',
            'client_nom'       => $data['client_nom'],
            'client_telephone' => $data['client_telephone'] ?? null,
            'date_emission'    => now()->toDateString(),
            'date_echeance'    => $data['date_echeance'] ?? null,
            'lignes'           => $data['lignes'] ?? [],
            'mode_paiement'    => $data['mode_paiement'] ?? null,
            'gabarit'          => $data['gabarit'] ?? 'standard',
            'acompte'          => $data['acompte'] ?? 0,
            'tva_taux'         => $prefs->assujetti_tva ? 18 : 0,
            'code_tracage'     => 'GX-T-' . Str::upper(Str::random(6)),
            'notes'            => $data['notes'] ?? null,
        ]);

        return response()->json($facture, 201);
    }

    // PATCH /api/factures/{facture}/statut
    public function updateStatut(Request $request, Facture $facture): JsonResponse
    {
        $this->authorizeFacture($request, $facture);

        $data = $request->validate([
            'statut'  => ['required', 'string', 'in:non_payee,acompte,soldee'],
            'acompte' => ['nullable', 'numeric', 'min:0'],
        ]);

        $facture->statut = $data['statut'];
        if (($data['acompte'] ?? null) !== null) {
            $facture->acompte = $data['acompte'];
        }
        $facture->save();

        return response()->json($facture);
    }

    // POST /api/factures/{facture}/dgi — joindre un PDF normalisé (intérim avant l'API e-MECeF).
    public function uploadDgi(Request $request, Facture $facture): JsonResponse
    {
        $this->authorizeFacture($request, $facture);

        $request->validate([
            'fichier' => ['required', 'file', 'mimes:pdf', 'max:20480'], // 20 Mo (PDF DGI scanné/lourd)
        ]);

        if ($facture->dgi_pdf_path) {
            Storage::disk('public')->delete($facture->dgi_pdf_path);
        }

        $facture->dgi_pdf_path = $request->file('fichier')->store('factures-dgi/' . $facture->atelier_id, 'public');
        $facture->save();

        return response()->json($facture);
    }

    // GET /api/factures/{facture}/dgi — sert le PDF DGI joint via l'API (même
    // origine + CORS api/* ok) pour l'habillage front, sans dépendre du CORS du stockage.
    public function downloadDgi(Request $request, Facture $facture)
    {
        $this->authorizeFacture($request, $facture);

        if (! $facture->dgi_pdf_path || ! Storage::disk('public')->exists($facture->dgi_pdf_path)) {
            abort(404);
        }

        return Storage::disk('public')->response($facture->dgi_pdf_path, 'facture-' . $facture->numero . '-dgi.pdf', [
            'Content-Type' => 'application/pdf',
        ]);
    }

    // DELETE /api/factures/{facture}
    public function destroy(Request $request, Facture $facture): JsonResponse
    {
        $this->authorizeFacture($request, $facture);

        if ($facture->dgi_pdf_path) {
            Storage::disk('public')->delete($facture->dgi_pdf_path);
        }
        $facture->delete();

        return response()->json(['message' => 'Document supprimé.']);
    }

    // POST /api/factures/{facture}/normaliser — normalisation DGI via e-MECeF (étape B).
    public function normaliser(Request $request, Facture $facture, EMecefService $emecef): JsonResponse
    {
        $this->authorizeFacture($request, $facture);

        $atelier = $this->getAtelier($request);
        if ($gate = $this->planGate($atelier, 'facturation_normalisee')) {
            return $gate;
        }
        $prefs = ParametresAtelier::firstOrNew(['atelier_id' => $atelier->id]);

        try {
            $facture = $emecef->normaliser($facture, $prefs);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($facture);
    }

    /**
     * Anciennes valeurs codées en dur dans le front, tolérées le temps de la bascule.
     * À supprimer une fois le front passé sur /moyens-paiement.
     */
    private const MODES_HERITES = ['wave', 'om', 'especes', 'virement', 'autre', 'mobile_money'];

    /** Modes acceptés = liste configurée + héritage transitoire. */
    private function modesAutorises(): array
    {
        $configures = collect(\App\Models\VitrineSetting::moyensPaiement())
            ->filter(fn ($m) => $m['actif'] ?? true)
            ->pluck('cle')
            ->all();

        return array_values(array_unique(array_merge($configures, self::MODES_HERITES)));
    }

    private function authorizeFacture(Request $request, Facture $facture): void
    {
        $atelier = $this->getAtelier($request);
        abort_unless($facture->atelier_id === $atelier->id, 403);
    }

    private function genererNumero(string $atelierId, string $type): string
    {
        $prefix = ['devis' => 'DEV', 'facture' => 'FAC', 'recu' => 'REC'][$type] ?? 'FAC';
        $annee  = now()->year;
        $n = Facture::where('atelier_id', $atelierId)
            ->where('type', $type)
            ->whereYear('created_at', $annee)
            ->count() + 1;

        return sprintf('%s-%d-%03d', $prefix, $annee, $n);
    }
}
