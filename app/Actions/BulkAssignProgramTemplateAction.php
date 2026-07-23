<?php

namespace App\Actions;

use App\Models\ProgramTemplate;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class BulkAssignProgramTemplateAction
{
    public function __construct(
        private readonly DuplicateProgramTemplateAction $duplicate,
        private readonly AssignProgramBlockAction $assign,
    ) {}

    /**
     * Assign a program template to several athletes at once. Each athlete gets
     * an independent copy of the template (so future edits stay isolated),
     * which is activated while their previous active block is archived.
     *
     * @param  array<int, int>  $athleteIds
     */
    public function execute(
        ProgramTemplate $source,
        User $coach,
        array $athleteIds,
        ?CarbonInterface $dateStart = null,
    ): int {
        $uniqueIds = array_values(array_unique(array_map('intval', $athleteIds)));

        return DB::transaction(function () use ($source, $coach, $uniqueIds, $dateStart): int {
            $count = 0;

            foreach ($uniqueIds as $athleteId) {
                $assignment = $this->duplicate->execute($source, $coach, $athleteId, $dateStart);
                $this->assign->execute($assignment);
                $count++;
            }

            return $count;
        });
    }
}
