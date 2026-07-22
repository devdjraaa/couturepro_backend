<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationSysteme;
use App\Models\Vetement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Modération des créations (vêtements) signalées.
 *
 * Un signalement de type « creation » archivait AUTOMATIQUEMENT le vêtement au
 * 3ᵉ signalement — depuis une route publique, sans compte ni limitation. Cette
 * sanction automatique a été retirée : n'importe qui pouvait faire disparaître
 * la création d'un créateur en trois requêtes.
 *
 * Il fallait donc l'équivalent manuel, sinon un signalement fondé restait sans
 * suite possible. Comme pour les annonces, le retrait exige un MOTIF, recopié
 * dans l'avis envoyé au créateur : retirer sans dire pourquoi n'est pas modérer.
 */
class CreationController extends Controller
{
    public function archiver(Request $request, Vetement $vetement): JsonResponse
    {
        $data = $request->validate([
            'motif' => ['required', 'string', 'max:300'],
        ]);

        $vetement->update(['is_archived' => true]);

        NotificationSysteme::create([
            'atelier_id' => $vetement->atelier_id,
            'titre'      => 'Création retirée',
            'contenu'    => 'Votre création « ' . $vetement->nom . ' » a été retirée de la vitrine : ' . $data['motif'],
            'type'       => 'moderation',
            'lien'       => '/catalogue',
            'is_read'    => false,
        ]);

        return response()->json(['vetement' => $vetement->fresh()]);
    }

    public function retablir(Vetement $vetement): JsonResponse
    {
        $vetement->update(['is_archived' => false]);

        return response()->json(['vetement' => $vetement->fresh()]);
    }
}
