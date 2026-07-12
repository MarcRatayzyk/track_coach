<?php

namespace App\Policies;

use App\Models\User;

class CoachPolicy
{
    public function view(User $viewer, User $coach): bool
    {
        if ($coach->role !== 'coach') {
            return false;
        }

        if ($viewer->id === $coach->id) {
            return true;
        }

        if ($viewer->role !== 'athlete') {
            return false;
        }

        return $viewer->coaches()
            ->where('users.id', $coach->id)
            ->wherePivot('status', 'active')
            ->exists();
    }

    public function update(User $viewer, User $coach): bool
    {
        return $viewer->role === 'coach' && $viewer->id === $coach->id;
    }
}
