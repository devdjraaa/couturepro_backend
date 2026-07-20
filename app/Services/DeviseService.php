<?php

namespace App\Services;

use App\Models\VitrineSetting;

/**
 * CLI-1 — Deviner le pays d'un atelier, et en déduire sa devise.
 *
 * La colonne `devise` existait, avec `XOF` en dur par défaut, et **aucun pays
 * n'était jamais détecté** : un atelier ghanéen ou nigérian se voyait attribuer
 * le franc CFA dès l'inscription, sans que rien ne le signale.
 *
 * La déduction se fait à partir du **numéro de téléphone**, seule donnée
 * géographique demandée à l'inscription. C'est un indice, pas une certitude —
 * d'où un réglage modifiable ensuite dans les paramètres de l'atelier.
 */
class DeviseService
{
    /**
     * Code pays (ISO 2 lettres) déduit d'un numéro, ou `null`.
     *
     * Les indicatifs sont comparés **du plus long au plus court**. Sans cela,
     * « +1 » (Amérique du Nord) capterait tous les indicatifs qui commencent
     * par 1, et « +22 » masquerait « +229 ». C'est le piège classique de ce
     * genre de table, et il ne se voit qu'avec les pays concernés.
     */
    public function paysDepuisTelephone(?string $telephone): ?string
    {
        if (! $telephone) {
            return null;
        }

        $numero = preg_replace('/[^\d+]/', '', $telephone);
        if (! str_starts_with($numero, '+')) {
            // Un numéro sans indicatif ne dit rien du pays. Deviner ici
            // reviendrait à supposer le Bénin pour le monde entier.
            return null;
        }

        $indicatifs = VitrineSetting::indicatifsPays();
        uksort($indicatifs, fn ($a, $b) => strlen($b) <=> strlen($a));

        foreach ($indicatifs as $indicatif => $pays) {
            if (str_starts_with($numero, $indicatif)) {
                return $pays;
            }
        }

        return null;
    }

    /** Devise d'un pays ; retombe sur la devise par défaut si le pays est inconnu. */
    public function devisePourPays(?string $pays): string
    {
        $cfg = VitrineSetting::devisesParPays();

        return $cfg['pays'][$pays] ?? ($cfg['defaut']['devise'] ?? 'XOF');
    }

    /** Devise déduite directement d'un numéro de téléphone. */
    public function deviseDepuisTelephone(?string $telephone): string
    {
        return $this->devisePourPays($this->paysDepuisTelephone($telephone));
    }

    /**
     * Symbole et nombre de décimales d'une devise.
     *
     * `decimales` n'est pas cosmétique : le franc CFA ne se divise pas, le cedi
     * et le naira si. Formater un montant ghanéen sans décimale fausse la
     * facture.
     */
    public function format(string $devise): array
    {
        $cfg = VitrineSetting::devisesParPays();

        return $cfg['formats'][$devise] ?? [
            'symbole'   => $devise,
            'decimales' => $cfg['defaut']['decimales'] ?? 0,
        ];
    }

    /**
     * Montant habillé de sa devise : « 125 000 FCFA », « 1 250,50 GH₵ ».
     *
     * Sert les messages sortants (WhatsApp, notifications) qui écrivaient
     * « FCFA » en clair — donc la mauvaise monnaie pour tout atelier hors zone
     * franc, dans des messages envoyés directement à ses clients.
     */
    public function montant(float|int|null $valeur, ?string $devise = null): string
    {
        $devise ??= VitrineSetting::devisesParPays()['defaut']['devise'] ?? 'XOF';
        $f = $this->format($devise);

        return number_format((float) ($valeur ?? 0), $f['decimales'], ',', ' ') . ' ' . $f['symbole'];
    }

    /** Devise d'un atelier, telle qu'elle est réellement rangée. */
    public function deviseAtelier(?\App\Models\Atelier $atelier): string
    {
        $cfg = VitrineSetting::devisesParPays();

        return $atelier?->parametres?->devise ?: ($cfg['defaut']['devise'] ?? 'XOF');
    }

    /** Table complète servie au front, pour qu'il n'ait aucune correspondance en dur. */
    public function referentiel(): array
    {
        $cfg = VitrineSetting::devisesParPays();

        return [
            'defaut'  => $cfg['defaut'] ?? ['devise' => 'XOF', 'symbole' => 'FCFA', 'decimales' => 0],
            'formats' => $cfg['formats'] ?? [],
        ];
    }
}
