<?php

namespace App\Policies;

use App\Models\Competition;
use App\Models\User;

class CompetitionPolicy
{
    public function viewLive(User $user, Competition $competition): bool
    {
        if ($competition->athlete_id === $user->id) {
            return true;
        }

        if ($user->role !== 'coach') {
            return false;
        }

        return $user->athletes()
            ->where('athlete_id', $competition->athlete_id)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function updateLive(User $user, Competition $competition): bool
    {
        return $this->viewLive($user, $competition);
    }
}
