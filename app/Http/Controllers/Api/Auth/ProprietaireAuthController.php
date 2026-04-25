<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\InscriptionRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VerifierOtpRequest;
use App\Models\Abonnement;
use App\Models\Atelier;
use App\Models\ListeNoire;
use App\Models\NiveauConfig;
use App\Models\Proprietaire;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProprietaireAuthController extends Controller
{
    public function __construct(private OtpService $otpService) {}

    public function inscription(InscriptionRequest $request): JsonResponse
    {
        if (ListeNoire::estBloque('email', $request->email)) {
            return response()->json(['message' => 'Inscription non autorisée.'], 403);
        }

        if (ListeNoire::estBloque('telephone', $request->telephone)) {
            return response()->json(['message' => 'Inscription non autorisée.'], 403);
        }

        $proprietaire = Proprietaire::create([
            'nom'              => $request->nom,
            'prenom'           => $request->prenom,
            'telephone'        => $request->telephone,
            'email'            => $request->email,
            'password'         => $request->password,
            'question_secrete' => $request->question_secrete,
            'reponse_secrete'  => $request->reponse_secrete,
        ]);

        $this->otpService->generer($proprietaire->telephone, 'verification_inscription', $proprietaire->email);

        return response()->json([
            'message'    => 'Compte créé. Un code OTP a été envoyé au ' . $proprietaire->telephone,
            'telephone'  => $proprietaire->telephone,
        ], 201);
    }

    public function verifierOtp(VerifierOtpRequest $request): JsonResponse
    {
        $proprietaire = Proprietaire::where('telephone', $request->telephone)
            ->whereNull('telephone_verified_at')
            ->first();

        if (!$proprietaire) {
            return response()->json(['message' => 'Compte introuvable ou déjà vérifié.'], 404);
        }

        if (!$this->otpService->verifier($request->telephone, $request->code, 'verification_inscription')) {
            return response()->json(['message' => 'Code OTP invalide ou expiré.'], 422);
        }

        $proprietaire->update(['telephone_verified_at' => now()]);

        $atelier = Atelier::create([
            'proprietaire_id' => $proprietaire->id,
            'nom'             => 'Atelier de ' . $proprietaire->prenom,
            'is_maitre'       => true,
            'statut'          => 'actif',
            'essai_expire_at' => now()->addDays(14),
        ]);

        $niveauEssai = NiveauConfig::where('cle', 'standard_mensuel')->first();

        Abonnement::create([
            'atelier_id'            => $atelier->id,
            'niveau_cle'            => $niveauEssai?->cle ?? 'standard_mensuel',
            'statut'                => 'essai',
            'jours_restants'        => 14,
            'timestamp_debut'       => now(),
            'timestamp_expiration'  => now()->addDays(14),
            'config_snapshot'       => $niveauEssai?->config,
        ]);

        $token = $proprietaire->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message'      => 'Compte vérifié avec succès.',
            'token'        => $token,
            'proprietaire' => $proprietaire->only(['id', 'nom', 'prenom', 'email', 'telephone']),
            'atelier'      => $atelier->only(['id', 'nom', 'is_maitre', 'statut']),
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $proprietaire = Proprietaire::where('telephone', $request->telephone)->first();

        if (!$proprietaire || !Hash::check($request->password, $proprietaire->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        if (!$proprietaire->telephone_verified_at) {
            return response()->json(['message' => 'Téléphone non vérifié. Veuillez valider votre OTP.'], 403);
        }

        $token = $proprietaire->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token'        => $token,
            'proprietaire' => $proprietaire->only(['id', 'nom', 'prenom', 'email', 'telephone']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function renvoyerOtp(Request $request): JsonResponse
    {
        $data = $request->validate(['telephone' => ['required', 'string']]);

        $proprietaire = Proprietaire::where('telephone', $data['telephone'])
            ->whereNull('telephone_verified_at')
            ->first();

        if (!$proprietaire) {
            return response()->json(['message' => 'Compte introuvable ou déjà vérifié.'], 404);
        }

        $this->otpService->generer($proprietaire->telephone, 'verification_inscription', $proprietaire->email);

        return response()->json(['message' => 'Code OTP renvoyé.', 'telephone' => $proprietaire->telephone]);
    }

    public function me(Request $request): JsonResponse
    {
        $proprietaire = $request->user()->load('atelierMaitre.abonnement');

        return response()->json($proprietaire);
    }
}
