<?php

namespace App\Actions;

use App\Models\AthleteProgramAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ClaimSelfCoachedProgramAction
{
    public function __construct(private readonly DuplicateProgramTemplateAction $duplicate) {}

    public function execute(User $athlete, AthleteProgramAssignment $assignment): AthleteProgramAssignment
    {
        if (! $athlete->isSelfCoached() || (int) $assignment->athlete_id !== (int) $athlete->id) {
            return $assignment;
        }

        $assignment->loadMissing('template');
        $template = $assignment->template;

        if ($template === null || (int) $template->coach_id === (int) $athlete->id) {
            return $assignment;
        }

        $shared = AthleteProgramAssignment::query()
            ->where('template_id', $template->id)
            ->whereKeyNot($assignment->id)
            ->exists();

        if (! $shared) {
            $template->forceFill(['coach_id' => $athlete->id])->save();

            return $assignment;
        }

        DB::transaction(function () use ($athlete, $assignment, $template): void {
            $copy = $this->duplicate->copyTemplate($template, $athlete, $template->name);
            $assignment->forceFill(['template_id' => $copy->id])->save();
        });

        $assignment->unsetRelation('template');

        return $assignment;
    }

    public function claimAll(User $athlete): void
    {
        if (! $athlete->isSelfCoached()) {
            return;
        }

        AthleteProgramAssignment::query()
            ->where('athlete_id', $athlete->id)
            ->orderBy('id')
            ->get()
            ->each(fn (AthleteProgramAssignment $assignment) => $this->execute($athlete, $assignment));
    }
}
