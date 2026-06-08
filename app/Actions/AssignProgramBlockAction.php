<?php

namespace App\Actions;

use App\Models\AthleteProgramAssignment;
use Illuminate\Support\Facades\DB;

class AssignProgramBlockAction
{
    public function execute(AthleteProgramAssignment $assignment): AthleteProgramAssignment
    {
        return DB::transaction(function () use ($assignment): AthleteProgramAssignment {
            AthleteProgramAssignment::query()
                ->where('athlete_id', $assignment->athlete_id)
                ->whereKeyNot($assignment->id)
                ->where('status', 'active')
                ->update(['status' => 'archived']);

            $assignment->update(['status' => 'active']);

            return $assignment->fresh(['athlete:id,name', 'template.weeks']);
        });
    }
}
