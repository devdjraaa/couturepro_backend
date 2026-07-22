<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VitrineSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Veille opportunités : collecte par `veille:opportunites`, dépôt ouvert aux
// sources extérieures (jeton partagé), lecture par l'automate et par l'admin.
class VeilleController extends Controller
{
    /**
     * POST /api/veille/ingest — dépôt d'un relevé par une source extérieure
     * (en-tête X-Veille-Token). La collecte quotidienne passe désormais par
     * `veille:opportunites` et écrit directement ; cette route reste ouverte
     * pour toute source tierce qui voudrait alimenter la veille.
     */
    public function ingest(Request $request): JsonResponse
    {
        $tokenAttendu = config('services.veille_ingest.token');
        abort_if(! $tokenAttendu || ! hash_equals($tokenAttendu, (string) $request->header('X-Veille-Token')), 401);

        $data = $request->validate([
            'semaine'              => ['required', 'date_format:Y-m-d'],
            'items'                => ['required', 'array', 'max:100'],
            'items.*.titre'        => ['required', 'string', 'max:300'],
            'items.*.lien'         => ['required', 'string', 'max:600'],
            'items.*.ia_selection' => ['nullable', 'boolean'],
            'items.*.ia_rang'      => ['nullable', 'integer', 'min:1', 'max:50'],
            'items.*.ia_raison'    => ['nullable', 'string', 'max:600'],
        ]);

        $n = 0;
        foreach ($data['items'] as $item) {
            DB::table('gxt_veille_items')->updateOrInsert(
                ['semaine' => $data['semaine'], 'lien' => mb_substr($item['lien'], 0, 600)],
                [
                    'id'           => DB::table('gxt_veille_items')
                        ->where('semaine', $data['semaine'])->where('lien', $item['lien'])->value('id') ?? (string) Str::uuid(),
                    'titre'        => mb_substr($item['titre'], 0, 300),
                    'ia_selection' => (bool) ($item['ia_selection'] ?? false),
                    'ia_rang'      => $item['ia_rang'] ?? null,
                    'ia_raison'    => $item['ia_raison'] ?? null,
                    'created_at'   => now(),
                ]
            );
            $n++;
        }

        return response()->json(['ok' => true, 'recus' => $n]);
    }

    /**
     * GET /api/veille/jour — le relevé d'une journée, pour l'envoi du digest.
     *
     * La collecte est faite par `veille:opportunites`, qui interroge une
     * trentaine de sources et fait trancher Makila. n8n n'a donc plus à la
     * refaire pour envoyer le courriel : il lit ici ce qui a déjà été établi.
     * Deux collectes concurrentes écriraient la même table avec des critères
     * différents, et le relevé dépendrait de qui a fini en dernier.
     *
     * Même jeton partagé que l'ingestion : ces deux routes servent le même
     * automate, et une veille lisible sans jeton renseignerait un concurrent
     * sur ce que nous suivons.
     */
    public function jour(Request $request): JsonResponse
    {
        $tokenAttendu = config('services.veille_ingest.token');
        abort_if(! $tokenAttendu || ! hash_equals($tokenAttendu, (string) $request->header('X-Veille-Token')), 401);

        $jour = $request->query('jour') ?: now('Africa/Porto-Novo')->toDateString();

        $items = DB::table('gxt_veille_items')->where('semaine', $jour)
            ->orderByRaw('ia_selection desc, ia_rang asc nulls last, titre asc')
            ->get(['titre', 'lien', 'ia_selection', 'ia_rang', 'ia_raison']);

        return response()->json([
            'jour'      => $jour,
            'total'     => $items->count(),
            'selection' => $items->where('ia_selection', true)->values(),
            'autres'    => $items->where('ia_selection', false)->values(),
        ]);
    }

    /**
     * POST /api/veille/digest-envoye — l'automate signale qu'il a fait son travail.
     *
     * Une alerte posée DANS le workflow ne se déclenche pas si le workflow ne
     * tourne pas : n8n arrêté, planificateur désactivé, machine redémarrée —
     * les pannes les plus probables sont justement celles qu'il ne peut pas
     * signaler lui-même.
     *
     * On inverse donc la charge de la preuve : l'automate annonce sa réussite,
     * et c'est l'ABSENCE de cette annonce qui déclenche l'alerte, depuis le
     * planificateur Laravel qui, lui, est surveillé. Le silence devient un
     * signal au lieu d'être invisible.
     */
    public function digestEnvoye(Request $request): JsonResponse
    {
        $tokenAttendu = config('services.veille_ingest.token');
        abort_if(! $tokenAttendu || ! hash_equals($tokenAttendu, (string) $request->header('X-Veille-Token')), 401);

        VitrineSetting::updateOrCreate(
            ['cle' => 'veille_digest_dernier_envoi'],
            ['valeur' => [
                'jour'     => now('Africa/Porto-Novo')->toDateString(),
                'horodate' => now()->toIso8601String(),
                'articles' => (int) $request->input('total', 0),
                'retenus'  => (int) $request->input('retenus', 0),
            ]],
        );

        return response()->json(['ok' => true]);
    }

    /**
     * GET /admin/veille/config — recherches et mots-clés de pertinence.
     *
     * Ils étaient « éditables en base », donc éditables par personne : il
     * fallait une console sur le serveur. La direction, qui connaît le terrain
     * bien mieux que nous, peut désormais enrichir la recherche elle-même.
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'recherches' => VitrineSetting::veilleRecherches(),
            'mots_cles'  => VitrineSetting::veilleMotsCles(),
        ]);
    }

    /**
     * PUT /admin/veille/config — enregistrement des recherches et mots-clés.
     *
     * Les listes sont nettoyées ici et non à l'affichage : un doublon ou un
     * terme vide ferait interroger deux fois la même source, ou une source
     * vide, à chaque exécution.
     *
     * Aucune liste ne peut être vidée entièrement : une veille sans terme de
     * recherche ne remonte rien, et le constat n'arriverait que le lendemain,
     * dans un digest vide dont personne ne comprendrait la cause.
     */
    public function setConfig(Request $request): JsonResponse
    {
        $data = $request->validate([
            'recherches'           => ['required', 'array', 'min:1', 'max:120'],
            // `nullable` et non `required` : le nettoyage ci-dessous retire les
            // entrées vides et les doublons. Les refuser ferait échouer tout
            // l'enregistrement pour une ligne blanche restée dans le formulaire.
            'recherches.*'         => ['nullable', 'string', 'max:120'],
            'mots_cles'            => ['required', 'array'],
            'mots_cles.benin'      => ['required', 'array', 'min:1', 'max:200'],
            'mots_cles.benin.*'    => ['nullable', 'string', 'max:60'],
            'mots_cles.metier'     => ['required', 'array', 'min:1', 'max:200'],
            'mots_cles.metier.*'   => ['nullable', 'string', 'max:60'],
            'mots_cles.occasion'   => ['required', 'array', 'max:200'],
            'mots_cles.occasion.*' => ['nullable', 'string', 'max:60'],
        ]);

        $nettoyer = fn (array $l) => array_values(array_unique(array_filter(array_map('trim', $l))));

        $recherches = $nettoyer($data['recherches']);
        abort_if($recherches === [], 422, 'Il faut au moins un terme de recherche.');

        $mots = [];
        foreach (['benin', 'metier', 'occasion'] as $axe) {
            // La comparaison se fait en minuscules dans la notation : les
            // enregistrer tels quels laisserait un mot saisi en capitales sans
            // jamais correspondre à quoi que ce soit.
            $mots[$axe] = $nettoyer(array_map('mb_strtolower', $data['mots_cles'][$axe]));
        }
        abort_if($mots['benin'] === [] || $mots['metier'] === [], 422, 'Les axes Bénin et métier ne peuvent pas être vides.');

        VitrineSetting::updateOrCreate(['cle' => 'veille_recherches'], ['valeur' => $recherches]);
        VitrineSetting::updateOrCreate(['cle' => 'veille_mots_cles'], ['valeur' => $mots]);

        return response()->json(['recherches' => $recherches, 'mots_cles' => $mots]);
    }

    /**
     * GET /admin/veille — les derniers relevés, sélection de Makila en tête.
     *
     * La colonne s'appelle encore `semaine` : elle datait du rythme
     * hebdomadaire de l'automate. Elle porte désormais un JOUR. Le nom est
     * conservé pour ne pas casser la route d'ingestion, que des sources
     * extérieures peuvent appeler ; seul le pas de temps a changé.
     *
     * D'où 14 relevés au lieu de 8 : à raison d'un par jour, huit ne
     * couvraient plus qu'une semaine d'historique.
     */
    public function index(): JsonResponse
    {
        $semaines = DB::table('gxt_veille_items')
            ->select('semaine')->distinct()->orderByDesc('semaine')->limit(14)->pluck('semaine');

        $resultat = $semaines->map(function ($semaine) {
            $items = DB::table('gxt_veille_items')->where('semaine', $semaine)
                ->orderByRaw('ia_selection desc, ia_rang asc nulls last, titre asc')
                ->get(['titre', 'lien', 'ia_selection', 'ia_rang', 'ia_raison']);

            return [
                'semaine'   => $semaine,
                'selection' => $items->where('ia_selection', true)->values(),
                'autres'    => $items->where('ia_selection', false)->values(),
            ];
        });

        // L'incident remonte AVEC les relevés : c'est le seul écran qu'on ouvre
        // pour la veille, et une alerte qu'il faut aller chercher ailleurs
        // n'est pas une alerte.
        return response()->json([
            'releves'  => $resultat,
            'incident' => VitrineSetting::where('cle', 'veille_incident')->value('valeur'),
        ]);
    }
}
