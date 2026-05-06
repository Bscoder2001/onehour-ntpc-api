<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function bootstrap(ChatService $chatService)
    {
        $userId = (int) auth()->id();
        $chatService->touchPresence($userId);

        return $this->sendResponse('OK', 200, [
            'user' => $chatService->bootstrapMeta(),
            'pusher_key' => config('broadcasting.connections.pusher.key'),
            'pusher_cluster' => config('broadcasting.connections.pusher.options.cluster')
                ?: env('PUSHER_APP_CLUSTER', 'mt1'),
            'composer_max_chars' => (int) config('chat.composer_max_chars', 10000),
        ]);
    }

    public function presence(ChatService $chatService)
    {
        $chatService->touchPresence((int) auth()->id());

        return $this->sendResponse('OK', 200, []);
    }

    public function users(ChatService $chatService)
    {
        $users = $chatService->listDirectoryForAuthUser();

        return $this->sendResponse('OK', 200, ['users' => $users]);
    }

    public function conversation(Request $request, ChatService $chatService)
    {
        $validated = $request->validate([
            'peer_id' => 'required|integer|min:1',
        ]);

        $messages = $chatService->conversationForAuthUser((int) $validated['peer_id']);

        return $this->sendResponse('OK', 200, ['messages' => $messages]);
    }

    public function sendMessage(Request $request, ChatService $chatService)
    {
        $maxLen = (int) config('chat.composer_max_chars', 10000);
        $validated = $request->validate([
            'receiver_id' => 'required|integer|min:1',
            'message' => 'required|string|max:'.$maxLen,
        ]);

        $saved = $chatService->sendForAuthUser(
            (int) $validated['receiver_id'],
            $validated['message']
        );

        return $this->sendResponse('Message sent successfully', 200, [
            'message' => $saved,
        ]);
    }

    public function markRead(Request $request, ChatService $chatService)
    {
        $validated = $request->validate([
            'peer_id' => 'required|integer|min:1',
            'last_message_id' => 'sometimes|integer|min:1',
        ]);

        $chatService->markConversationReadForAuthUser(
            (int) $validated['peer_id'],
            isset($validated['last_message_id']) ? (int) $validated['last_message_id'] : null
        );

        return $this->sendResponse('OK', 200, []);
    }

    public function typing(Request $request, ChatService $chatService)
    {
        $validated = $request->validate([
            'peer_id' => 'required|integer|min:1',
            'typing' => 'required|boolean',
        ]);

        $chatService->broadcastTypingForAuthUser(
            (int) $validated['peer_id'],
            (bool) $validated['typing']
        );

        return $this->sendResponse('OK', 200, []);
    }

    public function acknowledgeDelivered(Request $request, ChatService $chatService)
    {
        $validated = $request->validate([
            'peer_id' => 'required|integer|min:1',
            'message_ids' => 'required|array|max:50',
            'message_ids.*' => 'integer|min:1',
        ]);

        $chatService->acknowledgeDeliveredForAuthUser(
            (int) $validated['peer_id'],
            array_map('intval', $validated['message_ids'])
        );

        return $this->sendResponse('OK', 200, []);
    }

    public function deleteMessageForEveryone(Request $request, ChatService $chatService)
    {
        $validated = $request->validate([
            'message_id' => 'required|integer|min:1',
        ]);

        $chatService->deleteMessageForEveryoneForAuthUser((int) $validated['message_id']);

        return $this->sendResponse('OK', 200, []);
    }
}
