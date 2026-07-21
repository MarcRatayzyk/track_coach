<?php

namespace App\Services;

use App\Models\AthleteProgramAssignment;
use App\Models\TrainingSession;
use App\Support\AthleteAdherenceCalculator;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AthleteSessionCoverageService
{
    public function __construct(
        private readonly AthleteAdherenceCalculator $adherenceCalculator,
    ) {}

    /**
     * @return array{
     *     planned: int,
     *     completed: int,
     *     missed: int,
     *     percentage: int|null,
     *     session_coverage: int|null,
     *     planned_lines: int,
     *     exact_lines: int,
     *     missed_exercises: int,
     *     mismatched_sets: int,
     * }
     */
    public function coverageBetween(
        int $athleteId,
        AthleteProgramAssignment $assignment,
        CarbonInterface $start,
        CarbonInterface $end,
        ?Collection $preloadedSessions = null,
    ): array {
        $metrics = $this->adherenceCalculator->between(
            $athleteId,
            $assignment,
            $start,
            $end,
            $preloadedSessions,
        );

        return [
            'planned' => $metrics['planned_sessions'],
            'completed' => $metrics['completed_sessions'],
            'missed' => $metrics['missed_sessions'],
            'percentage' => $metrics['percentage'],
            'session_coverage' => $metrics['session_coverage'],
            'planned_lines' => $metrics['planned_lines'],
            'exact_lines' => $metrics['exact_lines'],
            'missed_exercises' => $metrics['missed_exercises'],
            'mismatched_sets' => $metrics['mismatched_sets'],
        ];
    }

    public function latestSessionDates(Collection $athleteIds): Collection
    {
        if ($athleteIds->isEmpty()) {
            return collect();
        }

        return TrainingSession::query()
            ->selectRaw('athlete_id, MAX(session_date) as latest_session_date')
            ->whereIn('athlete_id', $athleteIds)
            ->groupBy('athlete_id')
            ->pluck('latest_session_date', 'athlete_id')
            ->map(fn ($date) => $date !== null ? Carbon::parse($date)->toDateString() : null);
    }
}
