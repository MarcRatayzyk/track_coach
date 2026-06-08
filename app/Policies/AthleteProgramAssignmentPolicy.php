<?php

namespace App\Policies;

use App\Models\AthleteProgramAssignment;
use App\Models\User;

class AthleteProgramAssignmentPolicy
{
    public function manage(User $user, AthleteProgramAssignment $assignment): bool
    {
        if ($user->role !== 'coach') {
            return false;
        }

        $assignment->loadMissing('template');

        return $assignment->template?->coach_id === $user->id;
    }
}
