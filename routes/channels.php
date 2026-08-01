<?php

use App\Models\MessageThread;
use App\Support\BillingAccess;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('threads.{threadId}', function ($user, int $threadId) {
    if (! BillingAccess::hasAppAccess($user)) {
        return false;
    }

    $thread = MessageThread::query()->find($threadId);

    if ($thread === null) {
        return false;
    }

    return $user->can('view', $thread);
});

Broadcast::channel('users.{userId}', function ($user, int $userId) {
    if (! BillingAccess::hasAppAccess($user)) {
        return false;
    }

    return (int) $user->id === $userId;
});
