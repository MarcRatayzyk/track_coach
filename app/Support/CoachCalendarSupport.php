<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\CoachCalendarReminder;
use App\Models\Competition;
use App\Models\User;
use Carbon\Carbon;

class CoachCalendarSupport
{
    /**
     * @return array{start: string, end: string}
     */
    public static function dateRange(): array
    {
        $now = Carbon::now()->startOfDay();

        return [
            'start' => $now->copy()->subMonths(4)->startOfMonth()->toDateString(),
            'end' => $now->copy()->addMonths(2)->endOfMonth()->toDateString(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $athleteIds
     * @return list<array<string, mixed>>
     */
    public static function blockEventsForCoach(User $coach, $athleteIds): array
    {
        if ($athleteIds->isEmpty()) {
            return [];
        }

        $range = self::dateRange();

        return AthleteProgramAssignment::query()
            ->whereIn('athlete_id', $athleteIds)
            ->where('status', 'active')
            ->whereDate('date_end', '>=', $range['start'])
            ->whereDate('date_start', '<=', $range['end'])
            ->with(['athlete:id,name', 'template:id,name'])
            ->orderBy('date_start')
            ->get()
            ->map(fn (AthleteProgramAssignment $assignment) => [
                'date_start' => $assignment->date_start?->toDateString(),
                'date_end' => $assignment->date_end?->toDateString(),
                'name' => $assignment->template?->name,
                'athlete_name' => $assignment->athlete?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $athleteIds
     * @return list<array<string, mixed>>
     */
    public static function competitionsForCoach(User $coach, $athleteIds): array
    {
        if ($athleteIds->isEmpty()) {
            return [];
        }

        $range = self::dateRange();

        return Competition::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('competition_date', '>=', $range['start'])
            ->whereDate('competition_date', '<=', $range['end'])
            ->with('athlete:id,name')
            ->orderBy('competition_date')
            ->get()
            ->map(fn (Competition $competition) => [
                'id' => $competition->id,
                'name' => $competition->name,
                'competition_date' => $competition->competition_date?->toDateString(),
                'goal' => $competition->goal,
                'location' => $competition->location,
                'athlete_name' => $competition->athlete?->name,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function remindersForCoach(User $coach): array
    {
        $range = self::dateRange();

        return CoachCalendarReminder::query()
            ->where('coach_id', $coach->id)
            ->whereDate('event_date', '>=', $range['start'])
            ->whereDate('event_date', '<=', $range['end'])
            ->with('athlete:id,name')
            ->orderBy('event_date')
            ->get()
            ->map(fn (CoachCalendarReminder $reminder) => [
                'id' => $reminder->id,
                'title' => $reminder->title,
                'event_date' => $reminder->event_date?->toDateString(),
                'notes' => $reminder->notes,
                'athlete_id' => $reminder->athlete_id,
                'athlete_name' => $reminder->athlete?->name,
            ])
            ->values()
            ->all();
    }
}
