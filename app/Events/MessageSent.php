<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('threads.'.$this->message->thread_id)];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing('sender:id,name');

        return [
            'message' => [
                'id' => $this->message->id,
                'thread_id' => $this->message->thread_id,
                'sender_id' => $this->message->sender_id,
                'content' => $this->message->content,
                'created_at' => $this->message->created_at?->toIso8601String(),
                'sender' => [
                    'id' => $this->message->sender?->id,
                    'name' => $this->message->sender?->name,
                ],
            ],
        ];
    }
}
