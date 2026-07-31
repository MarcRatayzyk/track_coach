<?php

namespace App\Support\ProgramImport;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use Illuminate\Support\Facades\DB;

class EnsureProgramWeeksForImport
{
    /**
     * Create missing weeks (and empty day slots) so bulk import can target them.
     *
     * @param  list<array<string, mixed>>  $operations
     */
    public function ensure(AthleteProgramAssignment $assignment, array $operations): void
    {
        $assignment->loadMissing('template.weeks.trainingDays');
        $template = $assignment->template;
        if ($template === null) {
            return;
        }

        $neededWeeks = collect($operations)
            ->pluck('week_number')
            ->map(fn ($n) => (int) $n)
            ->filter(fn (int $n) => $n >= 1)
            ->unique()
            ->sort()
            ->values();

        if ($neededWeeks->isEmpty()) {
            return;
        }

        $existing = $template->weeks->pluck('week_number')->map(fn ($n) => (int) $n)->all();
        $daysPerWeek = max(
            4,
            (int) $template->weeks->max(fn (ProgramWeek $w) => $w->trainingDays->max('day_number') ?? 0),
            collect($operations)->pluck('weekday')->map(fn ($n) => (int) $n)->max() ?: 0,
        );

        DB::transaction(function () use ($template, $neededWeeks, $existing, $daysPerWeek): void {
            foreach ($neededWeeks as $weekNumber) {
                if (in_array($weekNumber, $existing, true)) {
                    continue;
                }

                $week = $template->weeks()->create([
                    'week_number' => $weekNumber,
                    'block_type' => ProgramWeek::BLOCK_VOLUME,
                ]);

                for ($day = 1; $day <= $daysPerWeek; $day++) {
                    $week->trainingDays()->create([
                        'day_number' => $day,
                        'main_lift' => ProgramTrainingDay::LIFT_SQUAT,
                        'session_label' => "Jour {$day}",
                    ]);
                }
            }
        });
    }
}
