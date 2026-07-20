<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Avis;
use App\Models\GxtClient;
use App\Models\NotificationSysteme;
use App\Models\Vetement;
use App\Models\VitrineSetting;
use App\Traits\ResolvesAtelier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Avis v2 — décisions direction du 20/07 (document « décisions arrêtées »).
 *
 * L'avis vise un MODÈLE, le dépôt exige un compte client, un seul avis par
 * personne et par modèle, publication automatique, modération a posteriori.
 * Tous les seuils viennent de `VitrineSetting::moderationAvis()` (éditable en
 * admin) : rien n'est figé dans le code.
 */
class AvisController extends Controller
{
    use ResolvesAtelier;

    /**
     * POST /api/vitrine/createurs/{atelier}/avis — ANCIEN dépôt niveau créateur.
     *
     * Retiré (décision 1 : l'avis vise un modèle, pas le créateur). 410 le temps
     * que les anciens clients HTTP disparaissent, comme pour `moderer()`.
     */
    public function store(): JsonResponse
    {
        return response()->json([
            'message' => 'Les avis se déposent désormais sur un modèle précis, avec un compte client.',
        ], 410);
    }

    /**
     * POST /api/vitrine/creations/{vetement}/avis — dépôt d'un avis sur un modèle.
     *
     * Compte obligatoire (décision 3), un seul avis par compte et par modèle
     * (décision 2), champs tous obligatoires (décision 4 + correctif urgent),
     * publication immédiate (décision 6). Les photos, elles, attendent la
     * validation admin (décision 11).
     */
    public function storePourModele(Request $request, Vetement $vetement): JsonResponse
    {
        $client = auth('sanctum')->user();
        if (! $client instanceof GxtClient) {
            return response()->json([
                'message' => 'Créez un compte ou connectez-vous pour laisser un avis.',
                'code'    => 'auth_requise',   // le front mémorise l'avis et ouvre la connexion
            ], 401);
        }

        $atelier = $vetement->atelier;
        if (! $vetement->publie_vitrine || $vetement->is_archived || ! $atelier || $atelier->is_demo) {
            return response()->json(['message' => 'Modèle introuvable.'], 404);
        }

        $data = $request->validate([
            'auteur_nom' => ['required', 'string', 'max:80'],
            'note'       => ['required', 'integer', 'min:1', 'max:5'],
            // Correctif prioritaire (20/07) : le texte était facultatif, un avis
            // vide partait sans message. Les trois champs sont obligatoires.
            'texte'      => ['required', 'string', 'min:10', 'max:600'],
            'photos'     => ['nullable', 'array', 'max:3'],
            'photos.*'   => ['image', 'max:4096'],
        ]);

        $reglages = VitrineSetting::moderationAvis();

        // Décision 2 : un seul avis par compte et par modèle. L'index unique en
        // base ferme la course ; ce contrôle donne un message clair avant.
        if (Avis::where('gxt_client_id', $client->id)->where('vetement_id', $vetement->id)->exists()) {
            return response()->json([
                'message' => 'Vous avez déjà laissé un avis sur ce modèle.',
                'code'    => 'deja_avis',
            ], 422);
        }

        // Décision 8 : anti-spam — plafond d'avis par compte et par jour.
        $dernierJour = Avis::where('gxt_client_id', $client->id)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        if ($dernierJour >= (int) $reglages['max_avis_par_jour']) {
            return response()->json([
                'message' => 'Vous avez atteint le nombre d\'avis autorisés pour aujourd\'hui. Revenez demain.',
                'code'    => 'quota_avis',
            ], 429);
        }

        // Décision 8 : refus des avis dupliqués — même compte, même texte
        // (normalisé), quel que soit le modèle, sur les 30 derniers jours.
        $normalise = $this->normaliser($data['texte']);
        $duplique = Avis::where('gxt_client_id', $client->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->get(['texte'])
            ->contains(fn ($a) => $this->normaliser((string) $a->texte) === $normalise);
        if ($duplique) {
            return response()->json([
                'message' => 'Vous avez déjà publié ce même texte. Écrivez un avis propre à ce modèle.',
                'code'    => 'avis_duplique',
            ], 422);
        }

        // Décision 12 : mots bannis. L'avis est PUBLIÉ mais marqué « à vérifier
        // en priorité » plutôt que bloqué : une liste de mots produit des faux
        // positifs (mot contenu dans un mot innocent, homonymes) et bloquer un
        // avis légitime ferait plus de tort qu'une vérification rapide.
        $prioritaire = $this->contientMotBanni($normalise, $reglages['mots_bannis'] ?? []);

        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                $photos[] = $photo->store('avis', 'public');
            }
        }

        $avis = Avis::create([
            'atelier_id'    => $atelier->id,
            'vetement_id'   => $vetement->id,
            'gxt_client_id' => $client->id,
            'auteur_nom'    => $data['auteur_nom'],
            'note'          => $data['note'],
            'texte'         => $data['texte'],
            'photos'        => $photos ?: null,
            // Décision 11 : les photos attendent la validation admin AVANT d'être
            // visibles — le texte, lui, est publié immédiatement (décision 6).
            'photos_statut' => $photos ? Avis::PHOTOS_EN_ATTENTE : null,
            'statut'        => 'valide',
            // Décision 9 : champ prêt, logique « achat vérifié » développée plus tard.
            'achat_verifie'     => false,
            'revue_prioritaire' => $prioritaire,
        ]);

        if ($prioritaire) {
            // L'horodatage le fait entrer tout de suite dans la file admin.
            $avis->update(['signale_at' => now()]);
        }

        NotificationSysteme::create([
            'atelier_id' => $atelier->id,
            'titre'      => 'Nouvel avis reçu',
            'contenu'    => $data['auteur_nom'] . ' a laissé un avis ' . $data['note'] . '★ sur « ' . $vetement->nom . ' ».',
            'type'       => 'avis_recu',
            'lien'       => '/ma-vitrine',
            'is_read'    => false,
        ]);

        return response()->json([
            'message' => $photos
                ? 'Merci, votre avis est publié. Vos photos seront visibles après vérification.'
                : 'Merci, votre avis est publié.',
        ], 201);
    }

    // GET /api/avis — avis de mon atelier (créateur connecté, tous statuts).
    public function index(Request $request): JsonResponse
    {
        $atelier = $this->getAtelier($request);

        return response()->json(
            Avis::where('atelier_id', $atelier->id)->with('vetement:id,nom')->latest()->get()
        );
    }

    /**
     * POST /api/avis/{avis}/moderation — RETIRÉ (S08C-29).
     * Le créateur ne valide jamais les avis (décision 5) ; 410 le temps que les
     * anciens clients HTTP disparaissent.
     */
    public function moderer(): JsonResponse
    {
        return response()->json([
            'message' => 'Les avis sont désormais publiés automatiquement : la validation par le créateur a été retirée.',
        ], 410);
    }

    /**
     * POST /api/vitrine/avis/{avis}/signaler — signalement motivé (décision 7).
     *
     * Une empreinte (compte connecté, sinon clé visiteur) ne compte qu'UNE fois
     * par avis. Au seuil configuré, l'avis entre dans la file de modération ; un
     * motif grave l'y met immédiatement, sans attendre le seuil.
     *
     * L'avis n'est JAMAIS dépublié ici : un signalement déclenche une revue
     * humaine, pas une sanction automatique — sinon un seul signalement méchant
     * suffirait à faire disparaître un avis légitime (faille déjà corrigée le 19/07).
     */
    public function signaler(Request $request, Avis $avis): JsonResponse
    {
        $reglages = VitrineSetting::moderationAvis();
        $motifs   = array_merge($reglages['motifs_graves'] ?? [], ['autre']);

        $data = $request->validate([
            'motif'       => ['nullable', 'string', Rule::in($motifs)],
            'visitor_key' => ['nullable', 'string', 'max:64'],
        ]);

        $client    = auth('sanctum')->user();
        $empreinte = $client instanceof GxtClient
            ? 'client:' . $client->id
            : 'visiteur:' . ($data['visitor_key'] ?? '');

        if ($empreinte === 'visiteur:') {
            return response()->json(['message' => 'Signalement invalide.'], 422);
        }

        // Idempotent : re-signaler ne compte pas double et ne révèle rien.
        DB::table('avis_signalements')->insertOrIgnore([
            'id'         => (string) Str::uuid(),
            'avis_id'    => $avis->id,
            'empreinte'  => substr($empreinte, 0, 64),
            'motif'      => $data['motif'] ?? 'autre',
            'created_at' => now(),
        ]);

        $total = DB::table('avis_signalements')->where('avis_id', $avis->id)->count();
        $grave = in_array($data['motif'] ?? '', $reglages['motifs_graves'] ?? [], true);

        $avis->update([
            'signalements_count' => $total,
            'signale_at'         => now(),
            // Décision 7 : un motif grave passe la revue en priorité immédiate.
            'revue_prioritaire'  => $avis->revue_prioritaire || $grave,
        ]);

        return response()->json(['message' => 'Signalement enregistré. Merci, notre équipe va vérifier.']);
    }

    /** Minuscules + accents aplatis : sert au dédoublonnage et aux mots bannis. */
    private function normaliser(string $texte): string
    {
        $t = mb_strtolower(trim($texte));

        return strtr($t, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i', 'ô' => 'o', 'ö' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c',
        ]);
    }

    /**
     * Mot banni présent ? Comparaison en frontières de mots : un mot de la liste
     * contenu DANS un mot innocent ne doit pas se déclencher.
     */
    private function contientMotBanni(string $texteNormalise, array $motsBannis): bool
    {
        foreach ($motsBannis as $mot) {
            $mot = $this->normaliser((string) $mot);
            if ($mot === '') {
                continue;
            }
            if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote($mot, '/') . '(?![\p{L}\p{N}])/u', $texteNormalise)) {
                return true;
            }
        }

        return false;
    }
}
