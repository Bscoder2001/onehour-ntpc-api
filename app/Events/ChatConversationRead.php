<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Peer opened conversation up to message id — for read ticks on sender UI.
 */
class ChatConversationRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $notifyUserId,
        public int $readerId,
        public int $upToMessageId
    )
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.'.$this->notifyUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'conversation.read';
    }

    /**
     * @return array{reader_id:int,up_to_message_id:int}
     */
    public function broadcastWith(): array
    {
        return [
            'reader_id' => $this->readerId,
            'up_to_message_id' => $this->upToMessageId,
        ];
    }
}
