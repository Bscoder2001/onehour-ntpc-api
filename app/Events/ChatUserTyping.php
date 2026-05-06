<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Ephemeral typing indicator. Recipient subscribes to chat.{their user id}.
 * Extensible: add conversation_type, thread_id for group chats later.
 */
class ChatUserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $recipientId,
        public int $typerId,
        public string $typerName,
        public bool $typing
    )
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.'.$this->recipientId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }

    /**
     * @return array{typer_id:int,typer_name:string,typing:bool}
     */
    public function broadcastWith(): array
    {
        return [
            'typer_id' => $this->typerId,
            'typer_name' => $this->typerName,
            'typing' => $this->typing,
        ];
    }
}
