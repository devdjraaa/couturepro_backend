<?php

namespace App\Services;

use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\PointsFidelite;
use App\Models\PointsHistorique;
use Illuminate\Support\Facades\DB;

class PointsFideliteService
{
    public function creditPoints(
        string $atelierId,
        string $type,
        int $points,
        string $description,
        ?string $referenceId = null
    ): void {
        if ($points === 0) {
            return;
        }

        $solde = PointsFidelite::firstOrCreate(
            ['atelier_id' => $atelierId],
            ['solde_pts'  => 0]
        );

        $solde->increment('solde_pts', $points);

        PointsHistorique::create([
            'atelier_id'   => $atelierId,
            'type'         => $type,
            'points'       => $points,
            'description'  => $description,
            'reference_id' => $referenceId,
            'created_at'   => now(),
        ]);
    }

    public function alreadyCredited(string $atelierId, string $referenceId): bool
    {
        return PointsHistorique::where('atelier_id', $atelierId)
            ->where('reference_id', $referenceId)
            ->exists();
    }

    /**
     * Crédite les points d'une création de client ou de commande.
     *
     * ⚠️ Cette règle vivait UNIQUEMENT dans le service de synchronisation hors ligne :
     * un utilisateur travaillant sur le web ne gagnait donc JAMAIS de points sur ses
     * clients et commandes — un pan entier du programme de fidélité était inopérant.
     * La règle est remontée ici pour que les deux chemins (web et synchro) l'utilisent.
     *
     * Idempotent par `reference_id` : un même enregistrement ne peut être crédité
     * qu'une fois, y compris si le web crée puis la synchro repousse le même objet.
     *
     * @param  string  $entite  'clients' ou 'commandes'
     */
    public function crediterCreation(Atelier $atelier, string $entite, string $recordId): void
    {
        if (! in_array($entite, ['clients', 'commandes'], true)) {
            return;
        }

        if ($this->alreadyCredited($atelier->id, $recordId)) {
            return;
        }

        $config = $atelier->abonnement?->getConfigEffective() ?? [];

        [$pts, $type, $desc] = $entite === 'clients'
            ? [(int) ($config['pts_par_client'] ?? 0),   'client_cree',      'Client créé']
            // Le type historique reste `commande_validee` (données déjà en base),
            // mais la description dit la vérité : le crédit a lieu à la CRÉATION.
            : [(int) ($config['pts_par_commande'] ?? 0), 'commande_validee', 'Commande créée'];

        if ($pts <= 0) {
            return;
        }

        $this->creditPoints($atelier->id, $type, $pts, $desc, $recordId);
    }

    public function convertirEnBonus(Atelier $atelier): Abonnement
    {
        $abonnement = $atelier->abonnement;

        if (!$abonnement) {
            throw new \DomainException('Aucun abonnement actif pour cet atelier.');
        }

        $config  = $abonnement->getConfigEffective();
        $seuil   = (int) ($config['seuil_conversion_pts'] ?? 0);
        // Durée du bonus obtenu : pilotée par le plan (était figée à 31 jours pour
        // TOUS les plans, alors que le seuil varie de 10 000 à 100 000 points — le
        // plan Studio payait donc 10x plus cher le même bonus).
        $jours   = (int) ($config['bonus_jours_conversion'] ?? 31);

        $solde = PointsFidelite::where('atelier_id', $atelier->id)->first();
        $pts   = $solde?->solde_pts ?? 0;

        if ($seuil <= 0) {
            throw new \DomainException('La conversion de points n\'est pas activée sur ce plan.');
        }

        if ($pts < $seuil) {
            throw new \DomainException("Solde insuffisant : {$pts} pts (seuil : {$seuil} pts).");
        }

        if ($abonnement->bonus_actif) {
            throw new \DomainException('Un bonus est déjà actif. Attendez sa fin avant de convertir.');
        }

        DB::transaction(function () use ($atelier, $abonnement, $solde, $seuil, $jours) {
            // Déduire les points
            $solde->decrement('solde_pts', $seuil);

            PointsHistorique::create([
                'atelier_id'  => $atelier->id,
                'type'        => 'conversion',
                'points'      => -$seuil,
                'description' => "Conversion points → bonus {$jours} jours",
                'created_at'  => now(),
            ]);

            // Activer le bonus
            $abonnement->update([
                'bonus_actif'           => true,
                'bonus_jours_restants'  => $jours,
                'bonus_niveau_cle'      => $abonnement->niveau_cle,
                'bonus_timestamp_debut' => now(),
            ]);
        });

        return $abonnement->fresh();
    }
}
