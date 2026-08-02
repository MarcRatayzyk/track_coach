<?php

namespace App\Actions;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTemplate;
use Illuminate\Support\Facades\DB;

class DeleteProgramBlockAction
{
    public function execute(AthleteProgramAssignment $assignment): void
    {
        DB::transaction(function () use ($assignment): void {
            $templateId = $assignment->template_id;

            // Supprime uniquement ce bloc (assignment), pas les autres qui partagent éventuellement le même template.
            $assignment->delete();

            if ($templateId === null) {
                return;
            }

            $stillUsed = AthleteProgramAssignment::query()
                ->where('template_id', $templateId)
                ->exists();

            if (! $stillUsed) {
                ProgramTemplate::query()->whereKey($templateId)->delete();
            }
        });
    }
}
