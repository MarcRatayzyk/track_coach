<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use Illuminate\Support\Collection;

class ProgramHistorySupport
{
    public function __construct(
        private readonly AthleteAdherenceCalculator $adherenceCalculator,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function historyForAthlete(int $athleteId): array
    {
        $assignments = AthleteProgramAssignment::query()
            ->where('athlete_id', $athleteId)
            ->where(function ($query): void {
                $query->where('status', 'archived')
                    ->orWhereNotNull('archived_at');
            })
            ->with(['template.weeks.trainingDays.exercises', 'athlete.latestPr'])
            ->orderByDesc('date_end')
            ->get();

        return $assignments
            ->map(fn (AthleteProgramAssignment $assignment) => $this->metricsForAssignment($assignment))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function metricsForAssignment(AthleteProgramAssignment $assignment): array
    {
        $assignment->loadMissing(['template.weeks.trainingDays.exercises', 'athlete.latestPr']);

        $start = $assignment->date_start ?? now();
        $end = $assignment->date_end ?? now();
        $effectiveEnd = $end->isFuture() ? now() : $end;

        $adherence = $this->adherenceCalculator->between(
            $assignment->athlete_id,
            $assignment,
            $start,
            $effectiveEnd,
        );

        $volume = 0;
        $template = $assignment->template;

        if ($template !== null) {
            foreach ($template->weeks as $week) {
                foreach ($week->trainingDays as $day) {
                    foreach ($day->exercises as $exercise) {
                        $volume += ((int) ($exercise->sets ?? 0)) * ((int) ($exercise->reps ?? 0));
                    }
                }
            }
        }

        $pr = $assignment->athlete?->latestPr;

        return [
            'id' => $assignment->id,
            'name' => $template?->name,
            'status' => $assignment->status,
            'date_start' => $assignment->date_start?->toDateString(),
            'date_end' => $assignment->date_end?->toDateString(),
            'archived_at' => $assignment->archived_at?->toIso8601String(),
            'adherence_percentage' => $adherence['percentage'],
            'volume_sets_reps' => $volume,
            'sbd_total' => (int) (($pr?->squat ?? 0) + ($pr?->bench ?? 0) + ($pr?->deadlift ?? 0)),
            'one_rm' => [
                'squat' => (int) ($pr?->squat ?? 0),
                'bench' => (int) ($pr?->bench ?? 0),
                'deadlift' => (int) ($pr?->deadlift ?? 0),
            ],
        ];
    }

    /**
     * @param  Collection<int, AthleteProgramAssignment>|list<int>  $ids
     * @return list<array<string, mixed>>
     */
    public function compare(int $athleteId, array $ids): array
    {
        return AthleteProgramAssignment::query()
            ->where('athlete_id', $athleteId)
            ->whereIn('id', $ids)
            ->with(['template.weeks.trainingDays.exercises', 'athlete.latestPr'])
            ->get()
            ->map(fn (AthleteProgramAssignment $assignment) => $this->metricsForAssignment($assignment))
            ->values()
            ->all();
    }
}
