<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OtaEvenement;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * POST /app/ota-evenement — un appareil rapporte l'issue d'une tentative OTA.
 *
 * Le 22/07, la version 1.0.143 a échoué en silence sur un appareil de test :
 * rien ne l'aurait signalé sans un test manuel, branché. Un atelier dont la
 * mise à jour échoue en continu ne s'en plaint jamais — il ne sait même pas
 * qu'une version plus récente existe.
 *
 * L'échec est rapporté par le bundle PRÉCÉDENT, encore actif : c'est lui qui
 * tourne au moment où le téléchargement ou l'application du nouveau bundle
 * échoue, donc lui seul peut prévenir le serveur. D'où l'écouteur posé tôt,
 * dans `ConfirmationBundle`, plutôt que dans le bundle qui vient d'échouer et
 * qui, par définition, ne démarre pas.
 */
class OtaEvenementController extends Controller
{
    use ResolvesAtelier;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'app_id'    => ['required', 'string', 'max:60'],
            'version'   => ['required', 'string', 'max:30'],
            'evenement' => ['required', 'in:succes,echec_telechargement,echec_application'],
            'detail'    => ['nullable', 'string', 'max:300'],
        ]);

        OtaEvenement::create([
            ...$data,
            // Toléré sans atelier : l'app peut échouer une mise à jour avant
            // même la création du premier atelier (juste après l'inscription).
            'atelier_id' => $this->getAtelierOuNull($request)?->id,
            'created_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
