<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ProgramSchedule
{
    /** @var array<int, Collection<int, ProgramWeek>> */
    private static array $sortedWeeksCache = [];

    /**
     * @return Collection<int, ProgramWeek>
     */
    private static function sortedWeeksForTemplate(?object $template): Collection
    {
        if ($template === null) {
            return collect();
        }

        if (! $template->relationLoaded('weeks')) {
            $template->load('weeks');
        }

        $templateId = (int) $template->id;

        if (! isset(self::$sortedWeeksCache[$templateId])) {
            self::$sortedWeeksCache[$templateId] = $template->weeks
                ->sortBy('week_number')
                ->values();
        }

        return self::$sortedWeeksCache[$templateId];
    }
    public static function currentWeekForAssignment(AthleteProgramAssignment $assignment): ?ProgramWeek
    {
        $weeks = self::sortedWeeksForTemplate($assignment->template);
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
        $cachedWeekIndex = null;
        $cachedTrainingDays = null;

        while ($cursor->lte($last)) {
            if (! self::isDateWithinAssignment($assignment, $cursor)) {
                $cursor = $cursor->copy()->addDay();

                continue;
            }

            $week = self::weekForAssignmentOnDate($assignment, $cursor);
            $weekIndex = $week?->week_number;

            if ($weekIndex !== $cachedWeekIndex) {
                $cachedWeekIndex = $weekIndex;
                $cachedTrainingDays = $week?->trainingDays?->keyBy('day_number');
            }

            if ($cachedTrainingDays?->has($cursor->isoWeekday()) === true) {
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
        $weeks = self::sortedWeeksForTemplate($assignment->template);
        if ($weeks->isEmpty()) {
            return null;
        }

        $reference = $date->copy()->startOfDay();
        $start = $assignment->date_start->copy()->startOfDay();
        $weekIndex = (int) $start->diffInWeeks($reference) + 1;
        $week = $weeks->firstWhere('week_number', $weekIndex);

        return $week ?? $weeks->last();
    }

    private static function isDateWithinAssignment(
        AthleteProgramAssignment $assignment,
        CarbonInterface $date,
    ): bool {
        if ($date->lt($assignment->date_start->copy()->startOfDay())) {
            return false;
        }

        if ($assignment->date_end !== null && $date->gt($assignment->date_end->copy()->startOfDay())) {
            return false;
        }

        return true;
    }
}
