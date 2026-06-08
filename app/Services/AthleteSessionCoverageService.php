<?php

namespace App\Services;

use App\Models\AthleteProgramAssignment;
use App\Models\TrainingSession;
use App\Support\AthleteAdherenceCalculator;
use Carbon\CarbonInterface;

class AthleteSessionCoverageService
{
    public function __construct(
        private readonly AthleteAdherenceCalculator $adherenceCalculator,
    ) {}

    /**
     * @return array{
     *     planned: int,
     *     completed: int,
     *     percentage: int|null,
     *     session_coverage: int|null,
     *     planned_lines: int,
     *     exact_lines: int,
     * }
     */
    public function coverageBetween(
        int $athleteId,
        AthleteProgramAssignment $assignment,
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $metrics = $this->adherenceCalculator->between($athleteId, $assignment, $start, $end);

        return [
            'planned' => $metrics['planned_sessions'],
            'completed' => $metrics['completed_sessions'],
            'percentage' => $metrics['percentage'],
            'session_coverage' => $metrics['session_coverage'],
            'planned_lines' => $metrics['planned_lines'],
            'exact_lines' => $metrics['exact_lines'],
        ];
    }

    public function latestSessionDate(int $athleteId): ?string
    {
        $date = TrainingSession::query()
            ->where('athlete_id', $athleteId)
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->value('session_date');

        return $date?->toDateString();
    }
}
