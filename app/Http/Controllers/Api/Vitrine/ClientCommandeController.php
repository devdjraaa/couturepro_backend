<?php

namespace App\Http\Controllers\Api\Vitrine;

use App\Http\Controllers\Controller;
use App\Jobs\SendGxtCommandeEmail;
use App\Models\Atelier;
use App\Models\Avis;
use App\Models\Client;
use App\Models\Commande;
use App\Models\GxtReclamation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

// P202 / Espace Client v3 — Phase 2, approche « direct » (validée direction) :
// la commande du client vitrine devient une VRAIE Commande dans l'outil du designer
// (fiche client d'atelier créée/liée automatiquement via gxt_client_id). Le designer
// gère tout au même endroit ; le client suit l'avancement réel (etape) et est notifié
// par e-mail (Brevo) à chaque étape. P165 Phase 1 : mise en relation, pas de paiement.
class ClientCommandeController extends Controller
{
    /** POST /vitrine/client/commandes — le client passe commande chez un designer. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'atelier_id'   => ['required', 'uuid'],
            'instructions' => ['required', 'string', 'max:2000'],
            'type_profil'  => ['nullable', 'in:homme,femme,enfant,mixte'],
        ]);

        $atelier = Atelier::where('id', $data['atelier_id'])
            ->where('type', 'designer')
            ->where('is_demo', false)
            ->firstOrFail();

        $gxt = $request->user();
        $fiche = $this->ficheClient($atelier, $gxt, $data['type_profil'] ?? 'mixte');

        $commande = Commande::create([
            'atelier_id'      => $atelier->id,
            'client_id'       => $fiche->id,
            'created_by'      => $atelier->proprietaire_id,
            'created_by_role' => 'proprietaire',
            'statut'          => 'en_cours',
            'etape'           => 'commande',
            'source'          => 'vitrine',
            'gxt_client_id'   => $gxt->id,
            'date_commande'   => now()->toDateString(),
            'description'     => $data['instructions'],
        ]);

        // Client : confirmation « reçue ». Designer : alerte nouvelle commande vitrine.
        SendGxtCommandeEmail::dispatch($gxt->email, 'recue', $commande->reference, $atelier->nom);
        $this->notifierDesigner($atelier, $commande);

        return response()->json([
            'message'  => 'Commande envoyée au designer.',
            'commande' => $this->presente($commande->fresh()),
        ], 201);
    }

    /** GET /vitrine/client/commandes — mes commandes. */
    public function index(Request $request): JsonResponse
    {
        $commandes = Commande::where('gxt_client_id', $request->user()->id)
            ->with('atelier:id,nom')
            ->latest()
            ->get();

        return response()->json($commandes->map(fn ($c) => $this->presente($c))->values());
    }

    /** POST /vitrine/client/commandes/{commande}/avis — visible uniquement quand livrée. */
    public function avis(Request $request, Commande $commande): JsonResponse
    {
        $this->verifierProprietaire($request, $commande);

        if ($commande->statut !== 'livre') {
            return response()->json(['message' => 'Vous pourrez laisser un avis une fois la commande livrée.'], 422);
        }
        if (Avis::where('commande_id', $commande->id)->exists()) {
            return response()->json(['message' => 'Un avis a déjà été laissé pour cette commande.'], 422);
        }

        $data = $request->validate([
            'note'  => ['required', 'integer', 'between:1,5'],
            'texte' => ['nullable', 'string', 'max:1000'],
        ]);

        $gxt = $request->user();
        $avis = Avis::create([
            'atelier_id'    => $commande->atelier_id,
            'auteur_nom'    => trim(($gxt->prenom ?? '').' '.($gxt->nom ?? '')) ?: 'Client Gextimo',
            'note'          => $data['note'],
            'texte'         => $data['texte'] ?? null,
            'statut'        => 'en_attente', // modération existante inchangée
            'gxt_client_id' => $gxt->id,
            'commande_id'   => $commande->id,
        ]);

        return response()->json(['message' => 'Merci pour votre avis !', 'avis' => $avis], 201);
    }

    /** POST /vitrine/client/commandes/{commande}/reclamation — notifie designer + admin. */
    public function reclamation(Request $request, Commande $commande): JsonResponse
    {
        $this->verifierProprietaire($request, $commande);

        $data = $request->validate([
            'sujet'   => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $gxt = $request->user();
        $reclamation = GxtReclamation::create([
            'gxt_client_id' => $gxt->id,
            'commande_id'   => $commande->id,
            'atelier_id'    => $commande->atelier_id,
            'sujet'         => $data['sujet'],
        ]);
        $reclamation->messages()->create([
            'auteur_type' => 'client',
            'auteur_id'   => $gxt->id,
            'message'     => $data['message'],
        ]);

        SendGxtCommandeEmail::dispatch($gxt->email, 'reclamation_recue', $commande->reference, $commande->atelier->nom);
        $this->notifierReclamation($commande, $data['sujet'], $data['message']);

        return response()->json([
            'message'     => 'Réclamation enregistrée. Le designer et l’équipe Gextimo ont été prévenus.',
            'reclamation' => $reclamation->load('messages'),
        ], 201);
    }

    // ── privé ────────────────────────────────────────────────────────────────

    /** Fiche client d'atelier liée au compte vitrine (créée au premier contact). */
    private function ficheClient(Atelier $atelier, $gxt, string $typeProfil): Client
    {
        $fiche = Client::where('atelier_id', $atelier->id)
            ->where('gxt_client_id', $gxt->id)
            ->first();
        if ($fiche) {
            return $fiche;
        }

        $prenom = $gxt->prenom ?: ucfirst(strtok($gxt->email, '@'));
        $nom    = $gxt->nom ?: 'Vitrine';

        // unique(atelier_id, nom, prenom) : suffixe si homonyme déjà présent dans l'atelier.
        if (Client::where('atelier_id', $atelier->id)->where('nom', $nom)->where('prenom', $prenom)->exists()) {
            $nom .= ' '.strtoupper(substr($gxt->id, 0, 4));
        }

        return Client::create([
            'atelier_id'      => $atelier->id,
            'nom'             => $nom,
            'prenom'          => $prenom,
            'telephone'       => $gxt->telephone_whatsapp,
            'type_profil'     => $typeProfil,
            'created_by'      => $atelier->proprietaire_id,
            'created_by_role' => 'proprietaire',
            'gxt_client_id'   => $gxt->id,
        ]);
    }

    private function presente(Commande $c): array
    {
        return [
            'id'         => $c->id,
            'reference'  => $c->reference,
            'designer'   => $c->atelier?->nom,
            'statut'     => $c->statut,
            'etape'      => $c->etape,
            'created_at' => $c->created_at,
            'avis_possible' => $c->statut === 'livre' && ! Avis::where('commande_id', $c->id)->exists(),
        ];
    }

    private function verifierProprietaire(Request $request, Commande $commande): void
    {
        abort_if($commande->gxt_client_id !== $request->user()->id, 403, 'Cette commande ne vous appartient pas.');
    }

    private function notifierDesigner(Atelier $atelier, Commande $commande): void
    {
        $email = $atelier->proprietaire?->email;
        if (! $email) {
            return;
        }
        try {
            Mail::raw(
                "Bonjour,\n\nNouvelle commande vitrine {$commande->reference} reçue sur votre profil Gextimo."
                ."\n\nInstructions du client :\n{$commande->description}"
                ."\n\nOuvrez l'application pour l'accepter et démarrer le suivi.\n\n— Gextimo",
                fn ($m) => $m->to($email)->subject("Nouvelle commande vitrine {$commande->reference}")
            );
        } catch (\Throwable $e) {
            Log::warning('Notification designer commande vitrine échouée — '.$e->getMessage());
        }
    }

    private function notifierReclamation(Commande $commande, string $sujet, string $message): void
    {
        $destinataires = array_filter([
            $commande->atelier?->proprietaire?->email,
            config('novafriq.inscription_alert_email'), // équipe interne (direction@)
        ]);
        foreach (array_unique($destinataires) as $email) {
            try {
                Mail::raw(
                    "Réclamation sur la commande {$commande->reference}.\n\nSujet : {$sujet}\n\nMessage :\n{$message}"
                    ."\n\nMerci de traiter rapidement.\n\n— Gextimo",
                    fn ($m) => $m->to($email)->subject("⚠ Réclamation — commande {$commande->reference}")
                );
            } catch (\Throwable $e) {
                Log::warning('Notification réclamation échouée — '.$e->getMessage());
            }
        }
    }
}
