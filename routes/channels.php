<?php

use App\Models\MessageThread;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('threads.{threadId}', function ($user, int $threadId) {
    $thread = MessageThread::query()->find($threadId);

    if ($thread === null) {
        return false;
    }

    return $user->can('view', $thread);
});
