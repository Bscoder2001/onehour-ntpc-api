<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notifies original sender that recipient clients acknowledged delivery (in-app).
 * Not persisted. Extensible for read receipts / group threads.
 */
class ChatMessagesDelivered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  list<int>  $messageIds
     */
    public function __construct(
        public int $notifySenderId,
        public array $messageIds
    )
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.'.$this->notifySenderId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'messages.delivered';
    }

    /**
     * @return array{message_ids:list<int>}
     */
    public function broadcastWith(): array
    {
        return [
            'message_ids' => array_values(array_map('intval', $this->messageIds)),
        ];
    }
}
