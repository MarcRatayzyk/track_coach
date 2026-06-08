<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use Carbon\CarbonInterface;

class ProgramSchedule
{
    public static function currentWeekForAssignment(AthleteProgramAssignment $assignment): ?ProgramWeek
    {
        $template = $assignment->template;
        if ($template === null) {
            return null;
        }

        $weeks = $template->weeks->sortBy('week_number')->values();
        if ($weeks->isEmpty()) {
            return null;
        }

        $weekIndex = (int) $assignment->date_start->diffInWeeks(now()->startOfDay()) + 1;
        $week = $weeks->firstWhere('week_number', $weekIndex);

        return $week ?? $weeks->last();
    }

    public static function hasSessionOnDate(
        AthleteProgramAssignment $assignment,
        CarbonInterface $date,
    ): bool {
        return self::resolveTrainingDayForDate($assignment, $date) !== null;
    }

    public static function hasSessionToday(
        AthleteProgramAssignment $assignment,
        ?CarbonInterface $date = null,
    ): bool {
        return self::hasSessionOnDate($assignment, $date ?? now());
    }

    public static function hasAnySessionBetween(
        AthleteProgramAssignment $assignment,
        CarbonInterface $start,
        CarbonInterface $end,
    ): bool {
        $cursor = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        while ($cursor->lte($last)) {
            if (self::hasSessionOnDate($assignment, $cursor)) {
                return true;
            }
            $cursor = $cursor->copy()->addDay();
        }

        return false;
    }

    public static function resolveTrainingDayForDate(
        AthleteProgramAssignment $assignment,
        CarbonInterface $date,
    ): ?ProgramTrainingDay {
        $week = self::weekForAssignmentOnDate($assignment, $date);
        if ($week === null) {
            return null;
        }

        if (! $week->relationLoaded('trainingDays')) {
            $week->load('trainingDays');
        }

        return $week->trainingDays->firstWhere('day_number', $date->isoWeekday());
    }

    public static function weekForAssignmentOnDate(
        AthleteProgramAssignment $assignment,
        CarbonInterface $date,
    ): ?ProgramWeek {
        $template = $assignment->template;
        if ($template === null) {
            return null;
        }

        $weeks = $template->weeks->sortBy('week_number')->values();
        if ($weeks->isEmpty()) {
            return null;
        }

        $reference = $date->copy()->startOfDay();
        $start = $assignment->date_start->copy()->startOfDay();
        $weekIndex = (int) $start->diffInWeeks($reference) + 1;
        $week = $weeks->firstWhere('week_number', $weekIndex);

        return $week ?? $weeks->last();
    }
}
