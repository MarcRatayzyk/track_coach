<?php

namespace App\Actions;

use App\Models\AthleteProgramAssignment;

class ArchiveProgramAssignmentAction
{
    public function execute(AthleteProgramAssignment $assignment): AthleteProgramAssignment
    {
        $assignment->forceFill([
            'status' => 'archived',
            'archived_at' => now(),
        ])->save();

        return $assignment;
    }
}
