<?php

namespace App\Policies;

use App\Models\MessageThread;
use App\Models\User;

class MessageThreadPolicy
{
    public function view(User $user, MessageThread $thread): bool
    {
        return $thread->coach_id === $user->id || $thread->athlete_id === $user->id;
    }

    public function sendMessage(User $user, MessageThread $thread): bool
    {
        return $this->view($user, $thread);
    }
}
