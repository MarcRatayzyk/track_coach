<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\User;
use Carbon\CarbonInterface;

class AthleteTodaySessionPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forAthlete(User $athlete, ?CarbonInterface $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $dateString = $date->toDateString();

        $assignment = self::activeAssignment($athlete, $date);

        if ($assignment === null) {
            return [
                'status' => 'no_program',
                'date' => $dateString,
                'program_name' => null,
                'week_number' => null,
                'block_type' => null,
                'session' => null,
                'next_session_date' => null,
            ];
        }

        $assignment->loadMissing('template.weeks.trainingDays.exercises');

        $trainingDay = ProgramSchedule::resolveTrainingDayForDate($assignment, $date);

        if ($trainingDay !== null) {
            $week = ProgramSchedule::weekForAssignmentOnDate($assignment, $date);

            return [
                'status' => 'session',
                'date' => $dateString,
                'program_name' => $assignment->template?->name,
                'week_number' => $week?->week_number,
                'block_type' => $week?->block_type,
                'session' => ProgramSessionSerializer::trainingDayToPayload($trainingDay),
                'next_session_date' => null,
            ];
        }

        return [
            'status' => 'rest',
            'date' => $dateString,
            'program_name' => $assignment->template?->name,
            'week_number' => null,
            'block_type' => null,
            'session' => null,
            'next_session_date' => self::nextSessionDate($assignment, $date),
        ];
    }

    private static function activeAssignment(User $athlete, CarbonInterface $date): ?AthleteProgramAssignment
    {
        return $athlete->programAssignments()
            ->where('status', 'active')
            ->whereDate('date_start', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $date->toDateString());
            })
            ->with('template.weeks')
            ->latest('date_start')
            ->first();
    }

    private static function nextSessionDate(
        AthleteProgramAssignment $assignment,
        CarbonInterface $fromDate,
    ): ?string {
        $cursor = $fromDate->copy()->addDay();
        $end = $fromDate->copy()->addDays(14);

        if ($assignment->date_end !== null) {
            $assignmentEnd = $assignment->date_end->copy()->startOfDay();
            if ($assignmentEnd->lt($end)) {
                $end = $assignmentEnd;
            }
        }

        while ($cursor->lte($end)) {
            if (ProgramSchedule::hasSessionOnDate($assignment, $cursor)) {
                return $cursor->toDateString();
            }

            $cursor = $cursor->copy()->addDay();
        }

        return null;
    }
}
