<?php

namespace App\Http\Controllers;

use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Ramsey\Uuid\Uuid;

class ChatbotController extends Controller
{
    public function chat(Request $request) {
        if ($request->session_id) {
            $session_id = $request->session_id;
            $history = ChatHistory::where('session_id', '=', $request->session_id)
            ->where('user_id', '=', $request->user()->id)
            ->orderBy('created_at', 'asc')
            ->get();
            $history = $history->map(fn($chat) => [
                ['role' => 'user', 'content' => $chat->user_message],
                ['role' => 'assistant', 'content' => $chat->bot_response],
            ])
            ->flatten(1)
            ->toArray();
            $messages = array_merge($history, [
                ['role' => 'user', 'content' => $request->message]
            ]);
        } else {
            $session_id = (string) Uuid::uuid4();
            $messages = [['role' => 'user', 'content' => $request->message]];
        }

        $reply = Http::post('http://localhost:11434/api/generate', [
            'model' => 'mistral',
            'messages' => $messages,
            'stream' => false
        ]);
        return $reply->json();
    }
}
