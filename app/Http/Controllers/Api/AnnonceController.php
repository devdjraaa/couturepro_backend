<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Annonce;
use App\Models\VitrineSetting;
use App\Services\PaymentService;
use App\Traits\ResolvesAtelier;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * ANN-1..9 — Module « Annonces » côté professionnel (Espace Designer).
 *
 * Publication GRATUITE, quelle que soit la durée (1 à 10 jours). Une seule
 * annonce par jour et par atelier. L'historique est conservé (contrairement à
 * l'ancienne « annonce de collection » qui écrasait la précédente).
 */
class AnnonceController extends Controller
{
    use ResolvesAtelier;

    /** GET /annonces — historique complet de l'atelier (statuts déduits des dates). */
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $annonces = Annonce::where('atelier_id', $atelier->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'annonces' => $annonces,
            'quota'    => $this->infoQuota($atelier->id),
            'boost'    => VitrineSetting::boostAnnonce(),
        ]);
    }

    /**
     * GET /annonces/quota — peut-on publier aujourd'hui ? (+ tarifs du Boost)
     * Permet au front d'afficher le message d'information sans tenter la création.
     */
    public function quota(Request $request): JsonResponse
    {
        return response()->json([
            'quota' => $this->infoQuota($this->getAtelier($request)->id),
            'boost' => VitrineSetting::boostAnnonce(),
            'duree' => ['min' => Annonce::DUREE_MIN, 'max' => Annonce::DUREE_MAX],
        ]);
    }

    /** POST /annonces — crée une annonce (date de fin calculée par le serveur). */
    public function store(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if (! $this->peutPublier($atelier->id)) {
            return response()->json([
                'message' => 'Vous avez déjà publié une annonce aujourd\'hui. Réessayez demain, ou utilisez le Boost pour augmenter la visibilité de votre annonce en cours.',
                'code'    => 'quota_journalier',
            ], 429);
        }

        $data = $request->validate([
            'titre'       => ['required', 'string', 'max:120'],
            'message'     => ['required', 'string', 'max:500'],
            'date_debut'  => ['required', 'date_format:Y-m-d', 'after_or_equal:' . Annonce::aujourdhui()->toDateString()],
            'duree_jours' => ['required', 'integer', 'min:' . Annonce::DUREE_MIN, 'max:' . Annonce::DUREE_MAX],
        ], [
            'date_debut.after_or_equal' => 'La date de début ne peut pas être dans le passé.',
            'duree_jours.max'           => 'La durée maximale d\'une annonce est de ' . Annonce::DUREE_MAX . ' jours.',
        ]);

        $debut = CarbonImmutable::parse($data['date_debut'], Annonce::FUSEAU)->startOfDay();

        $annonce = Annonce::create([
            'atelier_id'  => $atelier->id,
            'titre'       => $data['titre'],
            'message'     => $data['message'],
            'date_debut'  => $debut->toDateString(),
            'duree_jours' => $data['duree_jours'],
            // Durée inclusive : 1 jour = le jour même.
            'date_fin'    => $debut->addDays($data['duree_jours'] - 1)->toDateString(),
        ]);

        return response()->json(['annonce' => $annonce], 201);
    }

    /** PUT /annonces/{annonce} — modifie une annonce pas encore diffusée. */
    public function update(Request $request, Annonce $annonce): JsonResponse
    {
        if ($resp = $this->refuserSiPasProprietaire($request, $annonce)) {
            return $resp;
        }
        if ($annonce->statut !== 'programmee') {
            return response()->json([
                'message' => 'Une annonce déjà diffusée ne peut plus être modifiée.',
            ], 422);
        }

        $data = $request->validate([
            'titre'       => ['sometimes', 'required', 'string', 'max:120'],
            'message'     => ['sometimes', 'required', 'string', 'max:500'],
            'duree_jours' => ['sometimes', 'required', 'integer', 'min:' . Annonce::DUREE_MIN, 'max:' . Annonce::DUREE_MAX],
        ]);

        if (isset($data['duree_jours'])) {
            // copy() : le cast `date` renvoie un Carbon MUTABLE — sans copie,
            // addDays() modifierait date_debut de l'instance en mémoire.
            $data['date_fin'] = $annonce->date_debut->copy()->addDays($data['duree_jours'] - 1)->toDateString();
        }

        $annonce->update($data);

        return response()->json(['annonce' => $annonce->fresh()]);
    }

    /** POST /annonces/{annonce}/image — bannière facultative. */
    public function image(Request $request, Annonce $annonce): JsonResponse
    {
        if ($resp = $this->refuserSiPasProprietaire($request, $annonce)) {
            return $resp;
        }

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        if ($annonce->image_path) {
            Storage::disk('public')->delete($annonce->image_path);
        }

        $path = $request->file('image')->store('annonces/' . $annonce->atelier_id, 'public');
        $annonce->update(['image_path' => $path, 'image_url' => url(Storage::url($path))]);

        return response()->json(['annonce' => $annonce->fresh()]);
    }

    /** DELETE /annonces/{annonce}/image — retirer la bannière (message seul). */
    public function retirerImage(Request $request, Annonce $annonce): JsonResponse
    {
        if ($resp = $this->refuserSiPasProprietaire($request, $annonce)) {
            return $resp;
        }

        if ($annonce->image_path) {
            Storage::disk('public')->delete($annonce->image_path);
        }
        $annonce->update(['image_path' => null, 'image_url' => null]);

        return response()->json(['annonce' => $annonce->fresh()]);
    }

    /**
     * POST /annonces/{annonce}/boost — lance le paiement du Boost (ANN-5/6).
     *
     * Les durées et prix proviennent de la configuration (jamais du client) : le
     * front affiche le tarif, mais c'est le serveur qui fait foi. Le Boost démarre
     * automatiquement à la date programmée, une fois le paiement confirmé.
     */
    public function boost(Request $request, Annonce $annonce, PaymentService $paiements): JsonResponse
    {
        if ($resp = $this->refuserSiPasProprietaire($request, $annonce)) {
            return $resp;
        }

        $durees = collect(VitrineSetting::boostAnnonce()['offres'] ?? [])->pluck('jours')->all();

        $data = $request->validate([
            'jours'      => ['required', 'integer', Rule::in($durees)],
            'date_debut' => ['required', 'date_format:Y-m-d'],
            'return_url' => ['nullable', 'string', 'max:500'],
        ], [
            'jours.in' => 'Durée de Boost invalide (' . implode(', ', $durees) . ' jours).',
        ]);

        $paiement = $paiements->initiateBoostAnnonce(
            $annonce,
            (int) $data['jours'],
            $data['date_debut'],
            'fedapay',
            $data['return_url'] ?? null
        );

        return response()->json([
            'paiement_id'  => $paiement->id,
            'montant'      => (int) $paiement->montant,
            'devise'       => $paiement->devise,
            'checkout_url' => $paiement->checkout_url,
        ], 201);
    }

    /** DELETE /annonces/{annonce} */
    public function destroy(Request $request, Annonce $annonce): JsonResponse
    {
        if ($resp = $this->refuserSiPasProprietaire($request, $annonce)) {
            return $resp;
        }

        if ($annonce->image_path) {
            Storage::disk('public')->delete($annonce->image_path);
        }
        $annonce->delete();

        return response()->json(['message' => 'Annonce supprimée.']);
    }

    /** Une seule annonce par jour et par atelier (jour calendaire, heure de Cotonou). */
    private function peutPublier(string $atelierId): bool
    {
        return ! Annonce::where('atelier_id', $atelierId)
            ->where('created_at', '>=', Annonce::aujourdhui()->utc())
            ->exists();
    }

    private function infoQuota(string $atelierId): array
    {
        $peut = $this->peutPublier($atelierId);

        return [
            'peut_publier'      => $peut,
            'prochaine_fenetre' => $peut ? null : Annonce::aujourdhui()->addDay()->toDateString(),
            'message'           => 'Chaque designer peut publier une seule annonce par jour. Pour augmenter la visibilité de votre annonce, utilisez la fonctionnalité Boost.',
        ];
    }

    /** Garde-fou d'appartenance (multi-ateliers du même propriétaire). */
    private function refuserSiPasProprietaire(Request $request, Annonce $annonce): ?JsonResponse
    {
        if (! in_array($annonce->atelier_id, $this->ateliersAutorises($request), true)) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return null;
    }
}
