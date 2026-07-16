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
use App\Models\NotificationSysteme;
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
            'nom_atelier'      => $request->nom_atelier,
            'type_atelier'     => $request->type ?: 'artisan',
            'telephone'        => $request->telephone,
            'email'            => $request->email,
            'password'         => $request->password,
            'question_secrete' => $request->question_secrete,
            'reponse_secrete'  => $request->reponse_secrete,
        ]);

        $otp = $this->otpService->generer($proprietaire->telephone, 'verification_inscription', $proprietaire->email);

        return response()->json(array_filter([
            'message'   => 'Compte créé. Un code OTP a été envoyé par email.',
            'telephone' => $proprietaire->telephone,
            'otp_debug' => $this->otpService->debugCode($otp),
        ]), 201);
    }

    public function verifierOtp(VerifierOtpRequest $request): JsonResponse
    {
        $telephone = Proprietaire::normalizePhone($request->telephone);

        $proprietaire = Proprietaire::where('telephone', $telephone)
            ->whereNull('telephone_verified_at')
            ->first();

        if (!$proprietaire) {
            return response()->json(['message' => 'Compte introuvable ou déjà vérifié.'], 404);
        }

        if (!$this->otpService->verifier($telephone, $request->code, 'verification_inscription')) {
            return response()->json(['message' => 'Code OTP invalide ou expiré.'], 422);
        }

        $proprietaire->update(['telephone_verified_at' => now()]);

        $atelier = Atelier::create([
            'proprietaire_id' => $proprietaire->id,
            // Nom saisi par l'utilisateur à l'inscription (zéro hardcode) ;
            // repli sur le prénom si absent (comptes anciens), sans préfixe en dur.
            'nom'             => $proprietaire->nom_atelier ?: $proprietaire->prenom,
            'type'            => $proprietaire->type_atelier ?: 'artisan',
            'is_maitre'       => true,
            'statut'          => 'actif',
            'essai_expire_at' => now()->addDays(14),
        ]);

        // Essai « accès complet » : le niveau dépend du type de compte (designer → Studio).
        $cleEssai    = NiveauConfig::cleEssaiPour($atelier->type);
        $niveauEssai = NiveauConfig::where('cle', $cleEssai)->first();

        Abonnement::create([
            'atelier_id'            => $atelier->id,
            'niveau_cle'            => $niveauEssai?->cle ?? $cleEssai,
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
            'atelier'      => $atelier->only(['id', 'nom', 'type', 'is_maitre', 'statut']),
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $proprietaire = Proprietaire::where('telephone', Proprietaire::normalizePhone($request->telephone))->first();

        if (!$proprietaire || !Hash::check($request->password, $proprietaire->password)) {
            return response()->json(['message' => 'Identifiants incorrects.'], 401);
        }

        if (!$proprietaire->telephone_verified_at) {
            // P147 : le front redirige vers la page OTP (renvoi + saisie) au lieu de laisser bloqué.
            return response()->json([
                'message'   => 'Téléphone non vérifié. Veuillez valider votre OTP.',
                'code'      => 'telephone_non_verifie',
                'telephone' => $proprietaire->telephone,
            ], 403);
        }

        $token = $proprietaire->createToken('auth_token')->plainTextToken;

        // (Plus de notification « Connexion réussie » : c'était du bruit à chaque login.)

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
        $telephone = Proprietaire::normalizePhone($data['telephone']);

        $proprietaire = Proprietaire::where('telephone', $telephone)
            ->whereNull('telephone_verified_at')
            ->first();

        if (!$proprietaire) {
            return response()->json(['message' => 'Compte introuvable ou déjà vérifié.'], 404);
        }

        $otp = $this->otpService->generer($proprietaire->telephone, 'verification_inscription', $proprietaire->email);

        return response()->json(array_filter([
            'message'   => 'Code OTP renvoyé.',
            'telephone' => $proprietaire->telephone,
            'otp_debug' => $this->otpService->debugCode($otp),
        ]));
    }

    // P123 : e-mail fictif saisi à l'inscription → l'OTP n'arrive jamais → compte bloqué.
    // Sortie de secours : corriger son e-mail (authentifié par téléphone + mot de passe,
    // uniquement pour un compte NON vérifié) puis renvoi de l'OTP sur le nouvel e-mail.
    public function corrigerEmail(Request $request): JsonResponse
    {
        $data = $request->validate([
            'telephone' => ['required', 'string'],
            'password'  => ['required', 'string'],
            'email'     => ['required', 'email', 'max:190'],
        ]);

        $proprietaire = Proprietaire::where('telephone', Proprietaire::normalizePhone($data['telephone']))
            ->whereNull('telephone_verified_at')
            ->first();

        if (!$proprietaire || !Hash::check($data['password'], $proprietaire->password)) {
            return response()->json(['message' => 'Identifiants incorrects ou compte déjà vérifié.'], 422);
        }

        if (Proprietaire::where('email', $data['email'])->where('id', '!=', $proprietaire->id)->exists()) {
            return response()->json(['message' => 'Cet e-mail est déjà utilisé par un autre compte.'], 422);
        }

        $proprietaire->update(['email' => $data['email']]);
        $otp = $this->otpService->generer($proprietaire->telephone, 'verification_inscription', $data['email']);

        return response()->json(array_filter([
            'message'   => 'E-mail corrigé. Un nouveau code OTP a été envoyé.',
            'otp_debug' => $this->otpService->debugCode($otp),
        ]));
    }

    public function me(Request $request): JsonResponse
    {
        $proprietaire = $request->user()->load('atelierMaitre.abonnement', 'atelierMaitre.parametres');

        return response()->json($proprietaire);
    }
}
