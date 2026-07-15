<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patron;
use App\Models\PatronAchat;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

// P161-163 (côté public/vitrine) : achat + récupération + téléchargement d'un patron.
// Le téléchargement est autorisé par le CODE DE TRANSACTION (affiché sur le reçu), qui
// reste valable après fermeture de session — c'est le mécanisme de récupération.
class PatronPublicController extends Controller
{
    public function __construct(private PaymentService $payments) {}

    /** POST /api/vitrine/patrons/{patron}/acheter — crée l'achat + initie le paiement FedaPay. */
    public function acheter(Request $request, Patron $patron): JsonResponse
    {
        abort_unless($patron->actif, 404, 'Patron indisponible.');

        $data = $request->validate([
            'acheteur_nom'   => ['required', 'string', 'max:120'],
            'acheteur_email' => ['nullable', 'email', 'max:150'],
            'acheteur_tel'   => ['nullable', 'string', 'max:30'],
        ]);

        // Il faut au moins un moyen de contact pour retrouver l'acheteur / envoyer le reçu.
        abort_if(empty($data['acheteur_email']) && empty($data['acheteur_tel']), 422, 'Indiquez un e-mail ou un téléphone.');

        $achat = PatronAchat::create([
            'patron_id'        => $patron->id,
            'code_transaction' => $this->genererCode(),
            'acheteur_nom'     => $data['acheteur_nom'],
            'acheteur_email'   => $data['acheteur_email'] ?? null,
            'acheteur_tel'     => $data['acheteur_tel'] ?? null,
            'montant'          => $patron->prix,
            'statut'           => 'pending',
        ]);

        // Après paiement, FedaPay renvoie le visiteur vers le reçu (avec son code).
        $returnUrl = rtrim(config('payment.frontend_url'), '/') . '/patrons/recu/' . $achat->code_transaction;

        $paiement = $this->payments->initiatePatron($achat, $returnUrl);

        return response()->json([
            'code_transaction' => $achat->code_transaction,
            'checkout_url'     => $paiement->checkout_url,
        ], 201);
    }

    /** GET /api/vitrine/patrons/achats/{code} — statut de la transaction + contenu (P162). */
    public function statut(string $code): JsonResponse
    {
        $achat = PatronAchat::with('patron')->where('code_transaction', $code)->first();

        abort_unless($achat, 404, 'Aucune transaction pour ce code.');

        return response()->json([
            'code_transaction' => $achat->code_transaction,
            'statut'           => $achat->statut,           // pending | paye | echoue
            'paye'             => $achat->estPaye(),
            'montant'          => $achat->montant,
            'acheteur_nom'     => $achat->acheteur_nom,
            'patron'           => [
                'titre'       => $achat->patron?->titre,
                'description' => $achat->patron?->description,
                'fichier_nom' => $achat->patron?->fichier_nom,
            ],
            // Lien de téléchargement actif uniquement une fois payé.
            'telechargement'   => $achat->estPaye()
                ? url("/api/vitrine/patrons/achats/{$achat->code_transaction}/telecharger")
                : null,
        ]);
    }

    /** GET /api/vitrine/patrons/achats/{code}/telecharger — flux du fichier si payé (P161/P163). */
    public function telecharger(string $code): StreamedResponse
    {
        $achat = PatronAchat::with('patron')->where('code_transaction', $code)->firstOrFail();

        abort_unless($achat->estPaye(), 403, 'Téléchargement disponible après paiement.');
        $patron = $achat->patron;
        abort_unless($patron && Storage::exists($patron->fichier_path), 404, 'Fichier introuvable.');

        $achat->increment('nb_telechargements');

        return Storage::download($patron->fichier_path, $patron->fichier_nom ?: 'patron');
    }

    private function genererCode(): string
    {
        // Code lisible, unique — sert de reçu et de clé de récupération.
        do {
            $code = 'GXP-' . Str::upper(Str::random(10));
        } while (PatronAchat::where('code_transaction', $code)->exists());

        return $code;
    }
}
