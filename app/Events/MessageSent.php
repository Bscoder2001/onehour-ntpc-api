<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  object{id:int,sender_id:int,receiver_id:int,message:string,sender_display_name?:string}  $payload
     */
    public function __construct(public object $payload)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('chat.'.$this->payload->receiver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array{id:int,sender_id:int,receiver_id:int,message:string,sender_display_name:string}
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->payload->id,
            'sender_id' => $this->payload->sender_id,
            'receiver_id' => $this->payload->receiver_id,
            'message' => $this->payload->message,
            'sender_display_name' => (string) ($this->payload->sender_display_name ?? ''),
        ];
    }
}