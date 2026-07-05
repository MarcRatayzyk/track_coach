<?php

namespace App\Console\Commands;

use App\Actions\ArchiveProgramAssignmentAction;
use App\Models\AthleteProgramAssignment;
use Illuminate\Console\Command;

class ArchiveCompletedProgramAssignmentsCommand extends Command
{
    protected $signature = 'programs:archive-completed';

    protected $description = 'Archive program assignments whose end date is in the past';

    public function handle(ArchiveProgramAssignmentAction $archive): int
    {
        $count = 0;

        AthleteProgramAssignment::query()
            ->whereIn('status', ['active', 'completed'])
            ->whereNotNull('date_end')
            ->whereDate('date_end', '<', now()->toDateString())
            ->each(function (AthleteProgramAssignment $assignment) use ($archive, &$count): void {
                $archive->execute($assignment);
                $count++;
            });

        $this->info("Archived {$count} program assignment(s).");

        return self::SUCCESS;
    }
}
