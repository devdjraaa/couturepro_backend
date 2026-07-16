<?php

namespace App\Http\Controllers\Api\Vitrine;

use App\Http\Controllers\Controller;
use App\Models\GxtClient;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Brief 16/07 (pt 1) : chatbot vitrine — mémoire complète des échanges (question,
// réponse, intention, feedback), anonyme (session_id) ou rattaché au client connecté.
// Conformité : le widget informe que les échanges sont enregistrés pour améliorer le service.
class ChatbotController extends Controller
{
    public function __construct(private ChatbotService $bot) {}

    /** POST /vitrine/chatbot/message — question → réponse (+ mémorisation). */
    public function message(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id'      => ['required', 'string', 'max:100'],
            'message'         => ['required', 'string', 'max:1000'],
            'conversation_id' => ['nullable', 'uuid'],
            'langue'          => ['nullable', 'in:fr,en'],
        ]);

        $user = auth('sanctum')->user();
        $clientId = $user instanceof GxtClient ? $user->id : null;
        $langue = $data['langue'] ?? 'fr';

        // Conversation existante (même session) ou nouvelle.
        $conversationId = null;
        if (! empty($data['conversation_id'])) {
            $conversationId = DB::table('gxt_chat_conversations')
                ->where('id', $data['conversation_id'])
                ->where('session_id', $data['session_id'])
                ->value('id');
        }
        if (! $conversationId) {
            $conversationId = (string) Str::uuid();
            DB::table('gxt_chat_conversations')->insert([
                'id' => $conversationId, 'gxt_client_id' => $clientId,
                'session_id' => $data['session_id'], 'langue' => $langue,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        } elseif ($clientId) {
            // Le visiteur vient de se connecter : rattacher la conversation à son compte.
            DB::table('gxt_chat_conversations')->where('id', $conversationId)
                ->whereNull('gxt_client_id')->update(['gxt_client_id' => $clientId, 'updated_at' => now()]);
        }

        ['reponse' => $reponse, 'intention' => $intention] = $this->bot->repondre($data['message'], $langue);

        $messageId = (string) Str::uuid();
        DB::table('gxt_chat_messages')->insert([
            'id' => $messageId, 'conversation_id' => $conversationId,
            'question' => mb_substr($data['message'], 0, 1000),
            'reponse' => $reponse, 'intention' => $intention, 'created_at' => now(),
        ]);
        DB::table('gxt_chat_conversations')->where('id', $conversationId)->update(['updated_at' => now()]);

        return response()->json([
            'conversation_id' => $conversationId,
            'message_id'      => $messageId,
            'reponse'         => $reponse,
            'intention'       => $intention,
        ]);
    }

    /** POST /vitrine/chatbot/feedback — pouce haut/bas sur une réponse (améliore la base). */
    public function feedback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_id' => ['required', 'uuid'],
            'session_id' => ['required', 'string', 'max:100'],
            'utile'      => ['required', 'boolean'],
        ]);

        // Garde-fou : on ne note que les messages de SA session.
        $ok = DB::table('gxt_chat_messages')
            ->join('gxt_chat_conversations', 'gxt_chat_conversations.id', '=', 'gxt_chat_messages.conversation_id')
            ->where('gxt_chat_messages.id', $data['message_id'])
            ->where('gxt_chat_conversations.session_id', $data['session_id'])
            ->exists();
        abort_unless($ok, 404);

        DB::table('gxt_chat_messages')->where('id', $data['message_id'])->update(['utile' => $data['utile']]);

        return response()->json(['ok' => true]);
    }

    /** GET /vitrine/chatbot/historique?session_id=… — reprise du fil (même session). */
    public function historique(Request $request): JsonResponse
    {
        $data = $request->validate(['session_id' => ['required', 'string', 'max:100']]);

        $conversation = DB::table('gxt_chat_conversations')
            ->where('session_id', $data['session_id'])
            ->orderByDesc('updated_at')->first();
        if (! $conversation) {
            return response()->json(['conversation_id' => null, 'messages' => []]);
        }

        $messages = DB::table('gxt_chat_messages')
            ->where('conversation_id', $conversation->id)
            ->orderBy('created_at')->limit(50)
            ->get(['id', 'question', 'reponse', 'utile', 'created_at']);

        return response()->json(['conversation_id' => $conversation->id, 'messages' => $messages]);
    }
}
