<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use Illuminate\Support\Collection;

class ProgramBlockPresenter
{
    /**
     * @return array<string, mixed>|null
     */
    public static function forAssignment(?AthleteProgramAssignment $assignment): ?array
    {
        if ($assignment === null) {
            return null;
        }

        $assignment->loadMissing([
            'athlete:id,name',
            'athlete.latestPr',
            'template.weeks.trainingDays.exercises',
        ]);

        $template = $assignment->template;
        $weeks = $template?->weeks ?? collect();
        $sessions = [];
        $daysPerWeek = 0;

        foreach ($weeks as $week) {
            foreach ($week->trainingDays as $day) {
                $day->setRelation('week', $week);
                $key = self::cellKey($week->week_number, $day->day_number);
                $sessions[$key] = ProgramSessionSerializer::trainingDayToPayload($day);
                $daysPerWeek = max($daysPerWeek, (int) $day->day_number);
            }
        }

        $latestPr = $assignment->athlete?->latestPr;

        return [
            'id' => $assignment->id,
            'athlete_id' => $assignment->athlete_id,
            'athlete_name' => $assignment->athlete?->name,
            'name' => $template?->name,
            'status' => $assignment->status,
            'date_start' => $assignment->date_start?->toDateString(),
            'date_end' => $assignment->date_end?->toDateString(),
            'week_count' => $weeks->count(),
            'days_per_week' => $daysPerWeek,
            'table_layout' => DayTableLayoutSupport::resolveSnapshot($template?->table_layout),
            'sessions' => $sessions,
            'athlete_one_rm' => [
                'squat' => (int) ($latestPr?->squat ?? 0),
                'bench' => (int) ($latestPr?->bench ?? 0),
                'deadlift' => (int) ($latestPr?->deadlift ?? 0),
            ],
        ];
    }

    /**
     * @param  Collection<int, AthleteProgramAssignment>  $assignments
     * @return list<array<string, mixed>>
     */
    public static function existingBlocksList(Collection $assignments): array
    {
        return $assignments->map(function (AthleteProgramAssignment $assignment): array {
            $assignment->loadMissing(['athlete:id,name', 'template.weeks']);

            return [
                'id' => $assignment->id,
                'athlete_name' => $assignment->athlete?->name,
                'name' => $assignment->template?->name,
                'status' => $assignment->status,
                'date_start' => $assignment->date_start?->toDateString(),
                'date_end' => $assignment->date_end?->toDateString(),
                'week_count' => $assignment->template?->weeks->count() ?? 0,
            ];
        })->values()->all();
    }

    public static function cellKey(int $weekNumber, int $weekday): string
    {
        return "{$weekNumber}-{$weekday}";
    }
}
