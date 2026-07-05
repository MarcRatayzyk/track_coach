<?php

namespace App\Events;

use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ThreadUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public MessageThread $thread,
        public User $recipient,
        public int $unreadCount,
        public int $totalUnread = 0,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('users.'.$this->recipient->id)];
    }

    public function broadcastAs(): string
    {
        return 'thread.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'thread_id' => $this->thread->id,
            'unread_count' => $this->unreadCount,
            'total_unread' => $this->totalUnread,
        ];
    }
}
