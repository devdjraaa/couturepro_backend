<?php

namespace App\Services;

use App\Models\Facture;
use App\Models\ParametresAtelier;
use RuntimeException;

/**
 * Point d'intégration UNIQUE avec le système e-MECeF de la DGI (Bénin).
 *
 * ÉTAPE A (actuelle) : la normalisation n'est pas branchée. On ne peut PAS
 * fabriquer/imiter une facture normalisée (illégal) — seul e-MECeF génère le
 * code + QR + signature officiels. En attendant l'accès sandbox + la doc API,
 * `normaliser()` lève une exception explicite et l'atelier peut joindre
 * manuellement son PDF normalisé (uploadDgi).
 *
 * ÉTAPE B : remplir `normaliser()` avec l'appel réel à l'API e-MECeF
 * (jeton de l'atelier dans ParametresAtelier::emecef_token), puis stocker
 * sur la facture : emecef_code, emecef_qr_url, emecef_data, normalisee_at.
 */
class EMecefService
{
    public function estConfigure(ParametresAtelier $prefs): bool
    {
        return $prefs->assujetti_tva && ! empty($prefs->emecef_token);
    }

    public function normaliser(Facture $facture, ParametresAtelier $prefs): Facture
    {
        if (! $this->estConfigure($prefs)) {
            throw new RuntimeException(
                "Normalisation e-MECeF indisponible : l'atelier doit être assujetti TVA et avoir un jeton e-MECeF configuré."
            );
        }

        // ÉTAPE B — à implémenter avec la doc/sandbox e-MECeF officielle :
        //   $reponse = appel API e-MECeF ($prefs->emecef_token, payload facture);
        //   $facture->update([
        //       'emecef_code'   => $reponse['code'],
        //       'emecef_qr_url' => $reponse['qr'],
        //       'emecef_data'   => $reponse,           // compteurs, signature…
        //       'normalisee_at' => now(),
        //   ]);
        throw new RuntimeException("Intégration e-MECeF non encore déployée (étape B).");
    }
}
