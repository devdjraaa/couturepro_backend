<?php

namespace App\Services;

use App\Models\Atelier;
use App\Models\Facture;
use App\Models\QuotaMensuel;
use App\Models\Vetement;
use Illuminate\Support\Facades\DB;

class AtelierLimitsService
{
    public function getConfig(Atelier $atelier): array
    {
        return $atelier->abonnement?->getConfigEffective() ?? [];
    }

    public function canCreateClient(Atelier $atelier): bool
    {
        $config = $this->getConfig($atelier);
        $max = $config['max_clients_par_mois'] ?? null;

        if ($max === null || (int) $max === -1) {
            return true;
        }

        $quota = QuotaMensuel::courant($atelier->id);

        return $quota->nb_clients_crees < $max;
    }

    public function canCreateCommande(Atelier $atelier): bool
    {
        $abonnement = $atelier->abonnement;

        if (!$abonnement) {
            return false;
        }

        // P156 : un abonnement expiré ne bloque plus tout — l'utilisateur continue avec les
        // limites du plan GRATUIT (getConfigEffective retombe sur la config free quand expiré).
        $config = $abonnement->getConfigEffective();
        $max = $config['max_commandes_par_mois'] ?? null;

        if ($max === null || (int) $max === -1) {
            return true;
        }

        return QuotaMensuel::courant($atelier->id)->nb_commandes_creees < $max;
    }

    /**
     * Motif de refus de publication sur la vitrine, ou null si autorisé.
     * Deux limites distinctes (logique métier direction, 16/07/2026) :
     *  - plans payants : cap de créations publiées SIMULTANÉMENT (25 Atelier / 50 Studio) ;
     *  - plan gratuit : quota d'ACTES de publication par période d'abonnement (5),
     *    remis à zéro à chaque date anniversaire — dépublier ne redonne pas de crédit.
     */
    public function publicationRefus(Atelier $atelier): ?string
    {
        $config = $this->getConfig($atelier);

        $max = $config['max_creations_vitrine'] ?? null;
        if ($max !== null && (int) $max !== -1) {
            $count = Vetement::where('atelier_id', $atelier->id)
                ->where('is_archived', false)
                ->where('publie_vitrine', true)
                ->count();

            if ($count >= (int) $max) {
                return "Limite de {$max} créations publiées sur la vitrine atteinte pour votre plan. "
                    . 'Dépubliez une création ou passez à un plan supérieur.';
            }
        }

        $parPeriode = $config['publications_par_periode'] ?? null;
        if ($parPeriode !== null) {
            $utilise = $this->publicationsUtilisees($atelier);

            if ($utilise >= (int) $parPeriode) {
                return "Limite de {$parPeriode} publications sur la vitrine atteinte pour cette période "
                    . "(offre gratuite). Le compteur repartira à zéro au début de votre prochaine période. "
                    . 'Passez à un plan payant pour publier sans attendre.';
            }
        }

        return null;
    }

    public function canPublishVetement(Atelier $atelier): bool
    {
        return $this->publicationRefus($atelier) === null;
    }

    /** Nombre d'actes de publication effectués pendant la période d'abonnement courante. */
    public function publicationsUtilisees(Atelier $atelier): int
    {
        return DB::table('publications_vitrine')
            ->where('atelier_id', $atelier->id)
            ->where('created_at', '>=', $this->debutPeriode($atelier))
            ->count();
    }

    /** Journalise un acte de publication (à appeler après chaque passage en « publié »). */
    public function logPublication(Atelier $atelier, string $vetementId): void
    {
        DB::table('publications_vitrine')->insert([
            'atelier_id'  => $atelier->id,
            'vetement_id' => $vetementId,
            'created_at'  => now(),
        ]);
    }

    /**
     * Motif de refus d'une nouvelle facture, ou null si autorisé.
     * Plan gratuit : 10 CLIENTS DIFFÉRENTS facturés par période — un client déjà
     * facturé pendant la période ne consomme plus de quota (factures illimitées
     * pour lui). Les devis ne consomment pas de quota.
     */
    public function factureRefus(Atelier $atelier, string $type, ?string $clientNom, ?string $clientTelephone): ?string
    {
        if ($type === 'devis') {
            return null;
        }

        $config = $this->getConfig($atelier);
        $max = $config['max_clients_factures_periode'] ?? null;
        if ($max === null || (int) $max === -1) {
            return null;
        }

        $identites = $this->clientsFacturesPeriode($atelier);
        $courante  = self::identiteClientFacture($clientNom, $clientTelephone);

        if ($identites->contains($courante) || $identites->count() < (int) $max) {
            return null;
        }

        return "Limite atteinte : {$max} clients différents facturés pendant cette période (offre gratuite). "
            . "Vous pouvez continuer à facturer ces {$max} clients sans limite. Pour facturer un nouveau "
            . 'client dès maintenant, passez à un plan payant — sinon le compteur repartira à zéro au début '
            . 'de votre prochaine période.';
    }

    /** Identités distinctes des clients facturés (factures + reçus) pendant la période courante. */
    public function clientsFacturesPeriode(Atelier $atelier): \Illuminate\Support\Collection
    {
        return Facture::where('atelier_id', $atelier->id)
            ->whereIn('type', ['facture', 'recu'])
            ->where('created_at', '>=', $this->debutPeriode($atelier))
            ->get(['client_nom', 'client_telephone'])
            ->map(fn ($f) => self::identiteClientFacture($f->client_nom, $f->client_telephone))
            ->unique()
            ->values();
    }

    /**
     * Identité stable d'un client de facture (pas de client_id sur les factures) :
     * téléphone normalisé (8 derniers chiffres, insensible à l'indicatif) sinon nom normalisé.
     */
    public static function identiteClientFacture(?string $nom, ?string $telephone): string
    {
        $tel = preg_replace('/\D+/', '', (string) $telephone);
        if (strlen($tel) >= 8) {
            return 'tel:' . substr($tel, -8);
        }

        return 'nom:' . mb_strtolower(trim(preg_replace('/\s+/', ' ', (string) $nom)));
    }

    /** Début de la période de quota courante (anniversaire d'abonnement, repli mois civil). */
    private function debutPeriode(Atelier $atelier): \Carbon\CarbonInterface
    {
        return $atelier->abonnement?->debutPeriodeCourante() ?? now()->startOfMonth();
    }

    public function incrementClients(Atelier $atelier): void
    {
        QuotaMensuel::courant($atelier->id)->increment('nb_clients_crees');
    }

    public function incrementCommandes(Atelier $atelier): void
    {
        QuotaMensuel::courant($atelier->id)->increment('nb_commandes_creees');
    }
}
