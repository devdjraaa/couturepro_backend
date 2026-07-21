<?php

namespace App\Traits;

use App\Models\Atelier;
use App\Models\EquipeMembre;
use Illuminate\Http\Request;

trait ResolvesAtelier
{
    /**
     * Variante tolérante : rend `null` au lieu de lever une erreur quand le
     * compte n'a pas encore d'atelier.
     *
     * `getAtelier()` déclare un retour non-nullable, mais `atelierMaitre` EST
     * null tant qu'aucun atelier n'a été créé — c'est-à-dire entre l'inscription
     * et la première configuration. L'appeler dans cet intervalle produit une
     * erreur 500 sur ce qui est un état parfaitement normal. À utiliser dès
     * qu'un écran peut être atteint avant la création de l'atelier.
     */
    protected function getAtelierOuNull(Request $request): ?Atelier
    {
        $user = $request->user();

        if ($user instanceof EquipeMembre) {
            return $user->atelier;
        }

        $atelierIdHeader = $request->header('X-Atelier-Id');
        if ($atelierIdHeader) {
            $atelier = Atelier::where('id', $atelierIdHeader)
                ->where('proprietaire_id', $user?->id)
                ->first();
            if ($atelier) {
                return $atelier;
            }
        }

        return $user?->atelierMaitre;
    }

    protected function getAtelier(Request $request): Atelier
    {
        // Même résolution que la variante tolérante, mais garantit un Atelier :
        // `atelierMaitre` EST null tant qu'aucun atelier n'a été créé (entre
        // l'inscription et la première configuration). Renvoyer null violait le
        // type de retour `: Atelier` → 500 BRUT (des centaines dans le log).
        // On répond désormais proprement, avec un code que le front peut
        // reconnaître pour rediriger vers la création d'atelier.
        $atelier = $this->getAtelierOuNull($request);

        if ($atelier === null) {
            abort(response()->json([
                'message' => "Aucun atelier n'est encore configuré pour ce compte.",
                'code'    => 'atelier_absent',
            ], 409));
        }

        return $atelier;
    }

    /**
     * Ids des ateliers auxquels l'utilisateur courant a légitimement accès.
     * - Propriétaire : tous ses ateliers (support multi-ateliers P72-73 : une commande peut
     *   viser un client/vêtement de n'importe lequel de SES ateliers, sans ressaisie).
     * - Membre d'équipe : uniquement son atelier.
     * Sert de garde-fou anti-IDOR : on ne référence jamais les données d'un autre propriétaire.
     */
    protected function ateliersAutorises(Request $request): array
    {
        $user = $request->user();

        if ($user instanceof EquipeMembre) {
            return [$user->atelier_id];
        }

        return Atelier::where('proprietaire_id', $user->id)->pluck('id')->all();
    }
}
