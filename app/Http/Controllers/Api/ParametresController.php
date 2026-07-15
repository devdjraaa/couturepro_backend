<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\ChecksPlanFeature;
use App\Traits\ResolvesAtelier;
use App\Models\Atelier;
use App\Models\CommunicationsConfig;
use App\Models\EquipeMembre;
use App\Models\ParametresAtelier;
use App\Models\Proprietaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ParametresController extends Controller
{
    use ResolvesAtelier, ChecksPlanFeature;
    public function updateProfil(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'       => ['required', 'string', 'max:255'],
            // Format téléphone : sinon une saisie non numérique est normalisée à
            // vide/null → violation NOT NULL en base → 500 au lieu d'un 422 clair.
            'telephone' => ['required', 'string', 'max:20', 'regex:/^\+?[\d\s]{6,}$/'],
            'email'     => ['nullable', 'email', 'max:255'],
        ]);

        $user = $request->user();
        if (! $user instanceof Proprietaire) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        $user->update($data);

        return response()->json([
            'nom'       => $user->nom,
            'telephone' => $user->telephone,
            'email'     => $user->email,
        ]);
    }

    public function updateAtelier(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nom'    => ['required', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'ville'  => ['nullable', 'string', 'max:100'],
            'contact_public' => ['sometimes', 'boolean'],
            'specialite' => ['sometimes', 'nullable', 'string', 'max:120'],
            'bio'        => ['sometimes', 'nullable', 'string', 'max:1000'],
            'instagram'  => ['sometimes', 'nullable', 'string', 'max:255'],
            'facebook'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'site_web'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'latitude'   => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'  => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ]);

        $atelier = $this->getAtelier($request);
        $atelier->update($data);

        return response()->json([
            'nom'            => $atelier->nom,
            'adresse'        => $atelier->adresse,
            'ville'          => $atelier->ville,
            'contact_public' => $atelier->contact_public,
            'specialite'     => $atelier->specialite,
            'bio'            => $atelier->bio,
            'instagram'      => $atelier->instagram,
            'facebook'       => $atelier->facebook,
            'site_web'       => $atelier->site_web,
            'latitude'       => $atelier->latitude,
            'longitude'      => $atelier->longitude,
            'banniere_url'   => $atelier->banniere_url,
            'banniere_type'  => $atelier->banniere_type,
        ]);
    }

    // P134 : bannière du profil (photo/GIF/vidéo). Image ≤ 4 Mo, vidéo ≤ 15 Mo.
    public function uploadAtelierBanniere(Request $request): JsonResponse
    {
        $request->validate([
            'banniere' => ['required', 'file', 'mimes:jpeg,jpg,png,webp,gif,mp4,webm', 'max:15360'],
        ]);

        $atelier = $this->getAtelier($request);
        $file    = $request->file('banniere');
        $type    = str_starts_with((string) $file->getMimeType(), 'video') ? 'video' : 'image';

        // Une image de plus de 4 Mo est refusée (seules les vidéos vont jusqu'à 15 Mo).
        if ($type === 'image' && $file->getSize() > 4 * 1024 * 1024) {
            return response()->json(['message' => 'Image trop lourde (max 4 Mo).'], 422);
        }

        if ($atelier->banniere_path) {
            Storage::disk('public')->delete($atelier->banniere_path);
        }

        $path = $file->store('ateliers/' . $atelier->id . '/banniere', 'public');
        $atelier->update(['banniere_path' => $path, 'banniere_type' => $type]);

        return response()->json(['banniere_url' => $atelier->banniere_url, 'banniere_type' => $type]);
    }

    public function supprimerAtelierBanniere(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($atelier->banniere_path) {
            Storage::disk('public')->delete($atelier->banniere_path);
        }
        $atelier->update(['banniere_path' => null, 'banniere_type' => null]);

        return response()->json(['message' => 'Bannière retirée.']);
    }

    public function getCommunications(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $config  = CommunicationsConfig::firstOrNew(['atelier_id' => $atelier->id]);

        return response()->json([
            'confirmation_commande' => (bool) $config->confirmation_commande,
            'rappel_livraison_j2'   => (bool) $config->rappel_livraison_j2,
            'commande_prete'        => (bool) $config->commande_prete,
            'whatsapp_enabled'      => (bool) $config->whatsapp_enabled,
        ]);
    }

    public function updateCommunications(Request $request): JsonResponse
    {
        $data = $request->validate([
            'confirmation_commande'  => ['boolean'],
            'rappel_livraison_j2'    => ['boolean'],
            'commande_prete'         => ['boolean'],
            'whatsapp_enabled'       => ['boolean'],
        ]);

        $atelier = $this->getAtelier($request);

        $config = CommunicationsConfig::updateOrCreate(
            ['atelier_id' => $atelier->id],
            $data
        );

        return response()->json([
            'confirmation_commande' => (bool) $config->confirmation_commande,
            'rappel_livraison_j2'   => (bool) $config->rappel_livraison_j2,
            'commande_prete'        => (bool) $config->commande_prete,
            'whatsapp_enabled'      => (bool) $config->whatsapp_enabled,
        ]);
    }

    public function changerMotDePasse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ancien'  => ['required', 'string'],
            'nouveau' => ['required', 'string', 'min:8'],
        ]);

        $user = $request->user();
        if (! $user instanceof Proprietaire) {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        if (! Hash::check($data['ancien'], $user->password)) {
            return response()->json(['message' => 'Mot de passe actuel incorrect.'], 422);
        }

        $user->update(['password' => $data['nouveau']]);

        // Sécurité : un changement de mot de passe révoque toutes les sessions
        // (y compris le token courant) → l'utilisateur doit se reconnecter.
        $user->tokens()->delete();

        return response()->json([
            'message'      => 'Mot de passe modifié avec succès. Veuillez vous reconnecter.',
            'must_relogin' => true,
        ]);
    }

    public function getPreferences(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $prefs   = ParametresAtelier::firstOrNew(['atelier_id' => $atelier->id]);

        return response()->json([
            'devise'       => $prefs->devise       ?? 'XOF',
            'unite_mesure' => $prefs->unite_mesure ?? 'cm',
        ]);
    }

    public function updatePreferences(Request $request): JsonResponse
    {
        $data = $request->validate([
            'devise'       => ['required', 'string', 'in:XOF,GNF,XAF,EUR,USD,GHS,NGN,MAD'],
            'unite_mesure' => ['required', 'string', 'in:cm,pouces'],
        ]);

        $atelier = $this->getAtelier($request);

        ParametresAtelier::updateOrCreate(
            ['atelier_id' => $atelier->id],
            $data
        );

        return response()->json($data);
    }

    // #36 — Langue préférée stockée et retournée
    public function getLangue(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $prefs   = ParametresAtelier::firstOrNew(['atelier_id' => $atelier->id]);

        return response()->json(['langue' => $prefs->langue ?? 'fr']);
    }

    public function updateLangue(Request $request): JsonResponse
    {
        $data = $request->validate([
            'langue' => ['required', 'string', 'in:fr,en,ar,pt,wo'],
        ]);

        $atelier = $this->getAtelier($request);
        ParametresAtelier::updateOrCreate(['atelier_id' => $atelier->id], $data);

        return response()->json($data);
    }

    // Préférences complètes (devise + mesure + langue) en un seul appel
    public function getPreferencesComplet(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $prefs   = ParametresAtelier::firstOrNew(['atelier_id' => $atelier->id]);

        return response()->json([
            'devise'       => $prefs->devise       ?? 'XOF',
            'unite_mesure' => $prefs->unite_mesure ?? 'cm',
            'langue'       => $prefs->langue       ?? 'fr',
            'theme'        => $prefs->theme        ?? 'light',
        ]);
    }

    // Paramètres de facturation (standard / personnalisée)
    public function getFacture(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);
        $prefs   = ParametresAtelier::firstOrNew(['atelier_id' => $atelier->id]);
        $config  = $atelier->abonnement?->getConfigEffective() ?? [];

        return response()->json([
            'format_facture'        => $prefs->format_facture ?? 'standard',
            'facture_logo_url'      => $prefs->facture_logo_url,
            'facture_ifu'           => $prefs->facture_ifu,
            'facture_rccm'          => $prefs->facture_rccm,
            'facture_pied_page'     => $prefs->facture_pied_page,
            'personnalisation_dispo'=> !empty($config['facture_personnalisee']),
            'assujetti_tva'         => (bool) $prefs->assujetti_tva,
            'emecef_configure'      => !empty($prefs->emecef_token), // jamais le jeton lui-même
            'atelier_nom'           => $atelier->nom,
            'atelier_adresse'       => $atelier->adresse,
            'atelier_ville'         => $atelier->ville,
        ]);
    }

    public function updateFacture(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        $data = $request->validate([
            'format_facture'    => ['required', 'string', 'in:standard,personnalise'],
            'facture_ifu'       => ['nullable', 'string', 'max:100'],
            'facture_rccm'      => ['nullable', 'string', 'max:100'],
            'facture_pied_page' => ['nullable', 'string', 'max:1000'],
            'assujetti_tva'     => ['sometimes', 'boolean'],
            'emecef_token'      => ['sometimes', 'nullable', 'string', 'max:2000'], // JWT e-MECeF (peut dépasser 255 car.)
        ]);

        if ($data['format_facture'] === 'personnalise') {
            if ($gate = $this->planGate($atelier, 'facture_personnalisee')) {
                return $gate;
            }
        }

        $prefs = ParametresAtelier::updateOrCreate(['atelier_id' => $atelier->id], $data);

        return response()->json([
            'format_facture'    => $prefs->format_facture,
            'facture_ifu'       => $prefs->facture_ifu,
            'facture_rccm'      => $prefs->facture_rccm,
            'facture_pied_page' => $prefs->facture_pied_page,
            'facture_logo_url'  => $prefs->facture_logo_url,
        ]);
    }

    public function uploadFactureLogo(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        if ($gate = $this->planGate($atelier, 'facture_personnalisee')) {
            return $gate;
        }

        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:1024'],
        ]);

        $prefs = ParametresAtelier::firstOrNew(['atelier_id' => $atelier->id]);

        if ($prefs->facture_logo_path) {
            Storage::disk('public')->delete($prefs->facture_logo_path);
        }

        $path = $request->file('logo')->store('factures/' . $atelier->id, 'public');
        $prefs->atelier_id = $atelier->id;
        $prefs->facture_logo_path = $path;
        $prefs->save();

        return response()->json(['facture_logo_url' => $prefs->facture_logo_url]);
    }

    public function uploadAtelierLogo(Request $request): JsonResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:1024'],
        ]);

        $atelier = $this->getAtelier($request);

        if ($atelier->logo_path) {
            Storage::disk('public')->delete($atelier->logo_path);
        }

        $path = $request->file('logo')->store('ateliers/' . $atelier->id, 'public');
        $atelier->update(['logo_path' => $path]);

        return response()->json(['logo_url' => $atelier->logo_url]);
    }

    // POST /api/parametres/demande-verification — le créateur demande le badge vérifié
    // (pièce justificative et/ou lien). L'admin tranche ensuite via /admin/ateliers/{id}/verifier.
    public function demanderVerification(Request $request): JsonResponse
    {
        $request->validate([
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'lien'     => ['nullable', 'string', 'max:255'],
        ]);

        $atelier = $this->getAtelier($request);

        if (! $request->hasFile('document') && ! $request->filled('lien')) {
            return response()->json(['message' => 'Fournissez un document ou un lien justificatif.'], 422);
        }

        if ($request->hasFile('document')) {
            if ($atelier->verification_doc_path) {
                Storage::disk('public')->delete($atelier->verification_doc_path);
            }
            $atelier->verification_doc_path = $request->file('document')->store('verifications/' . $atelier->id, 'public');
        }

        if ($request->filled('lien')) {
            $atelier->verification_lien = $request->input('lien');
        }

        $atelier->verification_demandee_a = now();
        $atelier->save();

        return response()->json(['message' => "Demande de vérification envoyée. Notre équipe l'examinera prochainement."]);
    }

    public function changerTypeCompte(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:artisan,designer'],
        ]);

        $atelier = $this->getAtelier($request);
        $atelier->update(['type' => $data['type']]);

        return response()->json([
            'type'    => $atelier->type,
            'message' => 'Type de compte mis à jour.',
        ]);
    }
}
