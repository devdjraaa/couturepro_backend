<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\ParametresAtelier;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Point d'intégration UNIQUE avec le système e-MECeF de la DGI (Bénin).
 *
 * On ne peut PAS fabriquer/imiter une facture normalisée (illégal) : seul
 * e-MECeF génère le Code MECeF/DGI + QR + signature. Ce service appelle l'API
 * officielle (SFE) : POST /invoice puis PUT /invoice/{uid}/confirm.
 *
 * ⚠️ Par défaut on tape l'environnement de TEST (config/emecef.php). Passer en
 * production = poser EMECEF_BASE_URL=https://sygmef.impots.bj/emcf dans le .env
 * APRÈS agrément du SFE par la DGI. Le jeton est propre à chaque atelier
 * (ParametresAtelier::emecef_token, chiffré).
 *
 * NB : non encore validé en conditions réelles — nécessite un jeton de test
 * (sygmef-test) pour être éprouvé avant toute mise en production.
 */
class EMecefService
{
    /** Type de document app -> type de facture e-MECeF. Seule la facture est normalisable. */
    private const TYPE_MAP = ['facture' => 'FV'];

    public function estConfigure(ParametresAtelier $prefs): bool
    {
        return $prefs->assujetti_tva && ! empty($prefs->emecef_token) && ! empty($prefs->facture_ifu);
    }

    public function normaliser(Facture $facture, ParametresAtelier $prefs): Facture
    {
        if (! $this->estConfigure($prefs)) {
            throw new RuntimeException(
                "Normalisation indisponible : l'atelier doit être assujetti TVA, avoir un IFU et un jeton e-MECeF configuré."
            );
        }

        $type = self::TYPE_MAP[$facture->type] ?? null;
        if (! $type) {
            throw new RuntimeException('Seules les factures peuvent être normalisées (pas les devis ni les reçus).');
        }

        if ($facture->normalisee_at) {
            throw new RuntimeException('Cette facture est déjà normalisée.');
        }

        $base    = rtrim((string) config('emecef.base_url'), '/');
        $timeout = (int) config('emecef.timeout', 20);
        $http    = fn () => Http::withToken($prefs->emecef_token)->acceptJson()->timeout($timeout);

        // 1) Création de la facture côté e-MECeF.
        $payload = $this->buildPayload($facture, $prefs, $type);
        $create  = $http()->post("{$base}/invoice", $payload);
        $create->throw();
        $createData = $create->json() ?? [];

        if (! empty($createData['errorCode'])) {
            throw new RuntimeException('e-MECeF (création) : ' . ($createData['errorDesc'] ?? $createData['errorCode']));
        }

        $uid = $createData['uid'] ?? null;
        if (! $uid) {
            throw new RuntimeException('e-MECeF : réponse de création invalide (uid manquant).');
        }

        // 2) Confirmation -> éléments de sécurité (Code MECeF/DGI, QR, compteurs).
        $confirm = $http()->put("{$base}/invoice/{$uid}/confirm");
        $confirm->throw();
        $sec = $confirm->json() ?? [];

        if (! empty($sec['errorCode'])) {
            throw new RuntimeException('e-MECeF (confirmation) : ' . ($sec['errorDesc'] ?? $sec['errorCode']));
        }

        $facture->update([
            'emecef_code'   => $sec['codeMECeFDGI'] ?? null,
            'emecef_qr_url' => $sec['qrCode'] ?? null,
            'emecef_data'   => ['uid' => $uid, 'create' => $createData, 'confirm' => $sec],
            'normalisee_at' => now(),
        ]);

        return $facture->fresh();
    }

    private function buildPayload(Facture $facture, ParametresAtelier $prefs, string $type): array
    {
        // Atelier assujetti -> TVA 18 % (groupe B). Prix unitaires en TTC (entiers FCFA).
        $items = collect($facture->lignes ?? [])->map(fn ($l) => [
            'name'     => (string) ($l['description'] ?? 'Article'),
            'price'    => (int) round((float) ($l['prix_unitaire'] ?? 0)),
            'quantity' => (float) ($l['quantite'] ?? 1),
            'taxGroup' => 'B',
        ])->values()->all();

        $payload = [
            'ifu'      => $prefs->facture_ifu,
            'type'     => $type,
            'items'    => $items,
            'operator' => ['name' => $facture->atelier?->nom ?? 'Atelier'],
            'payment'  => [[
                'name'   => $this->mapPaiement($facture->mode_paiement),
                'amount' => (int) round((float) $facture->total),
            ]],
        ];

        // Client facultatif (on n'envoie pas d'IFU client tant qu'on ne le valide pas).
        $client = array_filter([
            'name'    => $facture->client_nom,
            'contact' => $facture->client_telephone,
        ]);
        if ($client) {
            $payload['client'] = $client;
        }

        return $payload;
    }

    private function mapPaiement(?string $mode): string
    {
        return match ($mode) {
            'especes'           => 'ESPECES',
            'wave', 'om', 'momo' => 'MOBILEMONEY',
            'virement'          => 'VIREMENT',
            'carte'             => 'CARTEBANCAIRE',
            'cheque'            => 'CHEQUES',
            default             => 'AUTRE',
        };
    }
}
