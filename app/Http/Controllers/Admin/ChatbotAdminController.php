<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Brief 16/07 (pt 1) : tableau de bord interne du chatbot — repérer les questions
// fréquentes mal traitées (fallbacks, pouces bas) pour enrichir la base d'intentions / la FAQ.
class ChatbotAdminController extends Controller
{
    /** GET /admin/chatbot/analyse */
    public function analyse(): JsonResponse
    {
        $depuis = now()->subDays(30);

        return response()->json([
            'volume_30j' => (int) DB::table('gxt_chat_messages')->where('created_at', '>=', $depuis)->count(),
            'intentions_top' => DB::table('gxt_chat_messages')->where('created_at', '>=', $depuis)
                ->selectRaw('intention, count(*) as n')->groupBy('intention')->orderByDesc('n')->pluck('n', 'intention'),
            // Les questions SANS réponse (fallback) = matière première de nouvelles intentions/FAQ.
            'questions_sans_reponse' => DB::table('gxt_chat_messages')->where('created_at', '>=', $depuis)
                ->where('intention', 'fallback')->orderByDesc('created_at')->limit(40)
                ->get(['question', 'created_at']),
            // Les réponses jugées mauvaises = intentions à reformuler.
            'reponses_negatives' => DB::table('gxt_chat_messages')->where('created_at', '>=', $depuis)
                ->where('utile', false)->orderByDesc('created_at')->limit(40)
                ->get(['question', 'reponse', 'intention', 'created_at']),
            'taux_satisfaction' => (function () use ($depuis) {
                $notes = DB::table('gxt_chat_messages')->where('created_at', '>=', $depuis)->whereNotNull('utile');
                $total = (clone $notes)->count();

                return $total ? round(100 * (clone $notes)->where('utile', true)->count() / $total, 1) : null;
            })(),
        ]);
    }

    /** GET /admin/chatbot/contexte — connaissance injectée à la mini-IA locale. */
    public function contexte(): JsonResponse
    {
        $v = DB::table('vitrine_settings')->where('cle', 'chatbot_contexte')->value('valeur');

        return response()->json(['texte' => $v ? (json_decode($v, true)['texte'] ?? '') : '']);
    }

    /** PUT /admin/chatbot/contexte — édition sans code (direction/admin). */
    public function setContexte(Request $request): JsonResponse
    {
        $data = $request->validate(['texte' => ['required', 'string', 'max:8000']]);

        DB::table('vitrine_settings')->updateOrInsert(
            ['cle' => 'chatbot_contexte'],
            [
                'id'         => DB::table('vitrine_settings')->where('cle', 'chatbot_contexte')->value('id') ?? (string) Str::uuid(),
                'valeur'     => json_encode(['texte' => $data['texte']]),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['texte' => $data['texte']]);
    }

    /** GET /admin/chatbot/intents — base de connaissances. */
    public function intents(): JsonResponse
    {
        return response()->json(DB::table('gxt_chat_intents')->orderByDesc('priorite')->get());
    }

    /** PUT /admin/chatbot/intents — remplace/complète la base (édition sans code). */
    public function setIntents(Request $request): JsonResponse
    {
        $data = $request->validate([
            'intents'               => ['required', 'array', 'max:100'],
            'intents.*.code'        => ['required', 'string', 'max:60'],
            'intents.*.mots_cles'   => ['required', 'array', 'min:1'],
            'intents.*.mots_cles.*' => ['string', 'max:60'],
            'intents.*.reponse_fr'  => ['required', 'string', 'max:1000'],
            'intents.*.reponse_en'  => ['required', 'string', 'max:1000'],
            'intents.*.priorite'    => ['nullable', 'integer', 'min:0', 'max:100'],
            'intents.*.actif'       => ['nullable', 'boolean'],
        ]);

        foreach ($data['intents'] as $i) {
            DB::table('gxt_chat_intents')->updateOrInsert(
                ['code' => $i['code']],
                [
                    'id'         => DB::table('gxt_chat_intents')->where('code', $i['code'])->value('id') ?? (string) Str::uuid(),
                    'mots_cles'  => json_encode($i['mots_cles']),
                    'reponse_fr' => $i['reponse_fr'],
                    'reponse_en' => $i['reponse_en'],
                    'priorite'   => $i['priorite'] ?? 0,
                    'actif'      => $i['actif'] ?? true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        return response()->json(DB::table('gxt_chat_intents')->orderByDesc('priorite')->get());
    }
}
