<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use Carbon\Carbon;
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

    /**
     * Lundi ISO de la semaine de date_start — aligné sur cellDate() côté front.
     */
    public static function scheduleAnchor(AthleteProgramAssignment $assignment): CarbonInterface
    {
        return $assignment->date_start
            ->copy()
            ->startOfDay()
            ->startOfWeek(Carbon::MONDAY);
    }

    /**
     * Date couverte par le calendrier du bloc (pas seulement date_start → date_end).
     */
    public static function isDateOnSchedule(
        AthleteProgramAssignment $assignment,
        CarbonInterface $date,
    ): bool {
        return self::isDateWithinAssignment($assignment, $date);
    }

    public static function currentWeekForAssignment(AthleteProgramAssignment $assignment): ?ProgramWeek
    {
        return self::weekForAssignmentOnDate($assignment, now())
            ?? self::sortedWeeksForTemplate($assignment->template)->last();
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
        if (! self::isDateWithinAssignment($assignment, $date)) {
            return null;
        }

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

        $weekIndex = self::weekIndexForDate($assignment, $date);
        if ($weekIndex === null) {
            return null;
        }

        return $weeks->firstWhere('week_number', $weekIndex);
    }

    /**
     * Index de semaine 1-based, même formule que cellDate (front).
     */
    public static function weekIndexForDate(
        AthleteProgramAssignment $assignment,
        CarbonInterface $date,
    ): ?int {
        $anchor = self::scheduleAnchor($assignment);
        $reference = $date->copy()->startOfDay();
        $days = (int) $anchor->diffInDays($reference, false);

        if ($days < 0) {
            return null;
        }

        $weekIndex = intdiv($days, 7) + 1;
        $weekCount = self::sortedWeeksForTemplate($assignment->template)->count();

        if ($weekCount > 0 && $weekIndex > $weekCount) {
            return null;
        }

        return $weekIndex;
    }

    private static function isDateWithinAssignment(
        AthleteProgramAssignment $assignment,
        CarbonInterface $date,
    ): bool {
        $reference = $date->copy()->startOfDay();
        $anchor = self::scheduleAnchor($assignment);

        if ($reference->lt($anchor)) {
            return false;
        }

        if ($assignment->date_end !== null && $reference->gt($assignment->date_end->copy()->startOfDay())) {
            return false;
        }

        return self::weekIndexForDate($assignment, $reference) !== null;
    }
}
