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

    // Étape 1 : entrer l'email → OTP envoyé par email
    public function etape1(RecuperationEtape1Request $request): JsonResponse
    {
        $proprietaire = Proprietaire::where('email', $request->email)->first();

        if (!$proprietaire) {
            return response()->json(['message' => 'Aucun compte associé à cet email.'], 404);
        }

        $demande = DemandeRecuperation::create([
            'email'      => $request->email,
            'statut'     => 'etape_1',
            'ip_address' => $request->ip(),
            'otp_envoye' => true,
        ]);

        $this->otpService->generer($proprietaire->telephone, 'recuperation', $proprietaire->email);

        return response()->json([
            'message'    => 'Code OTP envoyé à votre adresse email.',
            'demande_id' => $demande->id,
        ]);
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

        if (!$this->otpService->verifier($proprietaire->telephone, $request->code, 'recuperation')) {
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
        $this->otpService->generer($request->telephone_nouveau, 'recuperation_nouveau', $demande->email);

        return response()->json([
            'message'    => 'Code OTP envoyé à votre adresse email pour confirmer le nouveau numéro.',
            'demande_id' => $demande->id,
        ]);
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

        if (!$this->otpService->verifier($demande->telephone_nouveau, $request->code, 'recuperation_nouveau')) {
            return response()->json(['message' => 'Code OTP invalide ou expiré.'], 422);
        }

        $demande->update(['statut' => 'etape_4']);

        return response()->json([
            'message'    => 'Nouveau numéro confirmé. Définissez votre nouveau mot de passe.',
            'demande_id' => $demande->id,
        ]);
    }

    // Étape 5 : nouveau mot de passe + mise à jour du compte
    public function etape5(RecuperationEtape5Request $request): JsonResponse
    {
        $demande = DemandeRecuperation::where('id', $request->demande_id)
            ->where('statut', 'etape_4')
            ->first();

        if (!$demande) {
            return response()->json(['message' => 'Demande invalide ou étape incorrecte.'], 422);
        }

        $proprietaire = Proprietaire::where('email', $demande->email)->firstOrFail();

        $proprietaire->update([
            'telephone'             => $demande->telephone_nouveau,
            'telephone_verified_at' => now(),
            'password'              => $request->password,
        ]);

        $demande->update([
            'statut'       => 'complete',
            'validated_at' => now(),
        ]);

        $token = $proprietaire->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Compte récupéré avec succès.',
            'token'   => $token,
        ]);
    }
}
