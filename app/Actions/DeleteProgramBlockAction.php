<?php

namespace App\Actions;

use App\Models\AthleteProgramAssignment;
use Illuminate\Support\Facades\DB;

class DeleteProgramBlockAction
{
    public function execute(AthleteProgramAssignment $assignment): void
    {
        DB::transaction(function () use ($assignment): void {
            $assignment->loadMissing('template');

            $assignment->template?->delete();
        });
    }
}
