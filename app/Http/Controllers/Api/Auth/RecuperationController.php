<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RecuperationEtape1Request;
use App\Http\Requests\Auth\RecuperationEtape2Request;
use App\Http\Requests\Auth\RecuperationEtape3Request;
use App\Http\Requests\Auth\RecuperationEtape4Request;
use App\Http\Requests\Auth\RecuperationEtape5Request;
use App\Models\DemandeRecuperation;
use App\Models\Proprietaire;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;

class RecuperationController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    // Étape 1 : entrer l'email OU le téléphone → OTP envoyé sur l'email associé
    public function etape1(RecuperationEtape1Request $request): JsonResponse
    {
        $proprietaire = $request->filled('telephone')
            ? Proprietaire::where('telephone', $request->telephone)->first()
            : Proprietaire::where('email', $request->email)->first();

        if (!$proprietaire) {
            return response()->json([
                'message' => $request->filled('telephone')
                    ? 'Aucun compte associé à ce numéro.'
                    : 'Aucun compte associé à cet email.',
            ], 404);
        }

        $demande = DemandeRecuperation::create([
            'email'      => $proprietaire->email,
            'statut'     => 'etape_1',
            'ip_address' => $request->ip(),
            'otp_envoye' => true,
        ]);

        $otp = $this->otpService->generer($proprietaire->telephone, 'recuperation_compte', $proprietaire->email);

        return response()->json(array_filter([
            'message'    => 'Code OTP envoyé à votre adresse email.',
            'demande_id' => $demande->id,
            'email'      => $proprietaire->email,
            'otp_debug'  => $this->otpService->debugCode($otp),
        ]));
    }

    // Étape 2 : vérifier OTP
    public function etape2(RecuperationEtape2Request $request): JsonResponse
    {
        $demande = DemandeRecuperation::where('id', $request->demande_id)
            ->where('statut', 'etape_1')
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Demande invalide ou étape incorrecte.'], 422);
        }

        $proprietaire = Proprietaire::where('email', $demande->email)->first();

        if (!$this->otpService->verifier($proprietaire->telephone, $request->code, 'recuperation_compte')) {
            return response()->json(['message' => 'Code OTP invalide ou expiré.'], 422);
        }

        $demande->update(['statut' => 'etape_2']);

        return response()->json([
            'message'    => 'OTP validé. Entrez votre nouveau numéro de téléphone.',
            'demande_id' => $demande->id,
        ]);
    }

    // Étape 3 : entrer le nouveau téléphone → OTP envoyé par email
    public function etape3(RecuperationEtape3Request $request): JsonResponse
    {
        $demande = DemandeRecuperation::where('id', $request->demande_id)
            ->where('statut', 'etape_2')
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Demande invalide ou étape incorrecte.'], 422);
        }

        $demande->update([
            'telephone_nouveau' => $request->telephone_nouveau,
            'statut'            => 'etape_3',
        ]);

        // OTP envoyé sur le même email pour confirmer la prise en charge du nouveau numéro
        $otp = $this->otpService->generer($request->telephone_nouveau, 'recuperation_nouveau_telephone', $demande->email);

        return response()->json(array_filter([
            'message'    => 'Code OTP envoyé à votre adresse email pour confirmer le nouveau numéro.',
            'demande_id' => $demande->id,
            'otp_debug'  => $this->otpService->debugCode($otp),
        ]));
    }

    // Étape 4 : vérifier OTP du nouveau téléphone
    public function etape4(RecuperationEtape4Request $request): JsonResponse
    {
        $demande = DemandeRecuperation::where('id', $request->demande_id)
            ->where('statut', 'etape_3')
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Demande invalide ou étape incorrecte.'], 422);
        }

        if (!$this->otpService->verifier($demande->telephone_nouveau, $request->code, 'recuperation_nouveau_telephone')) {
            return response()->json(['message' => 'Code OTP invalide ou expiré.'], 422);
        }

        $demande->update(['statut' => 'etape_4']);

        return response()->json([
            'message'    => 'Nouveau numéro confirmé. Définissez votre nouveau mot de passe.',
            'demande_id' => $demande->id,
        ]);
    }

    // Étape 5 : nouveau mot de passe (depuis etape_2 = simple reset, ou etape_4 = full recovery)
    public function etape5(RecuperationEtape5Request $request): JsonResponse
    {
        $demande = DemandeRecuperation::where('id', $request->demande_id)
            ->whereIn('statut', ['etape_2', 'etape_4'])
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Demande invalide ou étape incorrecte.'], 422);
        }

        $proprietaire = Proprietaire::where('email', $demande->email)->firstOrFail();

        $update = ['password' => $request->password];

        // Si l'utilisateur a changé de téléphone (étape 3+4), on l'applique
        if ($demande->statut === 'etape_4' && $demande->telephone_nouveau) {
            $update['telephone']             = $demande->telephone_nouveau;
            $update['telephone_verified_at'] = now();
        }

        $proprietaire->update($update);

        $demande->update([
            'statut'       => 'complete',
            'validated_at' => now(),
        ]);

        $token = $proprietaire->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Mot de passe réinitialisé avec succès.',
            'token'   => $token,
        ]);
    }

    /**
     * Récupération via question secrète — étape 1 :
     * On retourne la question secrète associée à un téléphone (sans la réponse).
     * Le frontend l'affiche pour que l'utilisateur sache à quoi répondre.
     */
    public function lireQuestionSecrete(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'telephone' => ['required', 'string'],
        ]);

        $proprietaire = Proprietaire::where('telephone', $request->telephone)->first();

        if (!$proprietaire) {
            return response()->json(['message' => 'Aucun compte associé à ce numéro.'], 404);
        }

        return response()->json([
            'question_secrete' => $proprietaire->question_secrete,
        ]);
    }

    /**
     * Récupération via question secrète — étape 2 :
     * Valide la réponse. Si correcte, retourne un token de session sans
     * forcer le changement de mot de passe (UX type Google "essayer autrement").
     */
    public function verifierQuestionSecrete(\Illuminate\Http\Request $request): JsonResponse
    {
        $request->validate([
            'telephone'       => ['required', 'string'],
            'reponse_secrete' => ['required', 'string'],
        ]);

        $proprietaire = Proprietaire::where('telephone', $request->telephone)->first();

        if (!$proprietaire) {
            return response()->json(['message' => 'Aucun compte associé à ce numéro.'], 404);
        }

        if (!\Illuminate\Support\Facades\Hash::check($request->reponse_secrete, $proprietaire->reponse_secrete)) {
            return response()->json(['message' => 'Réponse incorrecte.'], 422);
        }

        $token = $proprietaire->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie via question secrète.',
            'token'   => $token,
        ]);
    }
}
