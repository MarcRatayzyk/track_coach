<?php

namespace App\Policies;

use App\Models\AthleteProgramAssignment;
use App\Models\User;

class AthleteProgramAssignmentPolicy
{
    public function manage(User $user, AthleteProgramAssignment $assignment): bool
    {
        $assignment->loadMissing('template');

        if ($assignment->template?->coach_id !== $user->id) {
            return false;
        }

        if ($user->isSelfCoached()) {
            return (int) $assignment->athlete_id === (int) $user->id;
        }

        return $user->role === 'coach';
    }
}
