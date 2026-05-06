<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeletedForEveryone implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $messageId,
        public int $senderId,
        public int $receiverId
    )
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.'.$this->senderId),
            new Channel('chat.'.$this->receiverId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted_everyone';
    }

    /**
     * @return array{message_id:int}
     */
    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
        ];
    }
}
