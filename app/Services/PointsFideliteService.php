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

    public function convertirEnBonus(Atelier $atelier): Abonnement
    {
        $abonnement = $atelier->abonnement;

        if (!$abonnement) {
            throw new \DomainException('Aucun abonnement actif pour cet atelier.');
        }

        $config  = $abonnement->getConfigEffective();
        $seuil   = (int) ($config['seuil_conversion_pts'] ?? 0);

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

        DB::transaction(function () use ($atelier, $abonnement, $solde, $seuil) {
            // Déduire les points
            $solde->decrement('solde_pts', $seuil);

            PointsHistorique::create([
                'atelier_id'  => $atelier->id,
                'type'        => 'conversion',
                'points'      => -$seuil,
                'description' => 'Conversion points → bonus 31 jours',
                'created_at'  => now(),
            ]);

            // Activer le bonus
            $abonnement->update([
                'bonus_actif'           => true,
                'bonus_jours_restants'  => 31,
                'bonus_niveau_cle'      => $abonnement->niveau_cle,
                'bonus_timestamp_debut' => now(),
            ]);
        });

        return $abonnement->fresh();
    }
}
