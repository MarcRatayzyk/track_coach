<?php

namespace App\Policies;

use App\Models\SessionFeedback;
use App\Models\User;

class SessionFeedbackPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['coach', 'athlete'], true);
    }

    public function view(User $user, SessionFeedback $feedback): bool
    {
        if ($feedback->athlete_id === $user->id) {
            return true;
        }

        if ($user->role !== 'coach') {
            return false;
        }

        return $user->athletes()
            ->where('athlete_id', $feedback->athlete_id)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function create(User $user): bool
    {
        if ($user->role !== 'athlete') {
            return false;
        }

        return $user->coaches()
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function reply(User $user, SessionFeedback $feedback): bool
    {
        if ($user->role !== 'coach' || $feedback->coach_id !== $user->id) {
            return false;
        }

        return $feedback->isPendingCoachReply();
    }
}
