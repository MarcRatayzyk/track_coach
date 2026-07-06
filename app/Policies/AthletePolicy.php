<?php

namespace App\Policies;

use App\Models\User;

class AthletePolicy
{
    public function create(User $user): bool
    {
        return $user->role === 'coach';
    }

    public function detachFromRoster(User $user, User $athlete): bool
    {
        return $user->role === 'coach'
            && $athlete->role === 'athlete'
            && $user->athletes()->where('athlete_id', $athlete->id)->exists();
    }

    public function view(User $user, User $athlete): bool
    {
        if ($user->id === $athlete->id) {
            return true;
        }

        return $user->athletes()->where('athlete_id', $athlete->id)->exists();
    }

    public function updateAthleteData(User $user, User $athlete): bool
    {
        return $user->role === 'coach'
            && $user->athletes()->where('athlete_id', $athlete->id)->exists();
    }

    public function recordOwnPr(User $user, User $athlete): bool
    {
        return $user->id === $athlete->id && $athlete->role === 'athlete';
    }

    public function proposeCompetitionMatchPlan(User $user, User $athlete): bool
    {
        return $user->id === $athlete->id && $athlete->role === 'athlete';
    }

    public function manageOwnCompetitions(User $user, User $athlete): bool
    {
        return $user->id === $athlete->id && $athlete->role === 'athlete';
    }
}
