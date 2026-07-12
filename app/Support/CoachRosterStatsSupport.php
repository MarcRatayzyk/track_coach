<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\Competition;
use App\Models\User;
use App\Services\AthleteSessionCoverageService;

class CoachRosterStatsSupport
{
    /**
     * @return array<string, mixed>
     */
    public static function forCoach(User $coach): array
    {
        $today = now()->copy()->startOfDay();
        $periodStart = $today->copy()->subDays(29);

        $athletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->get(['users.id']);

        $athleteIds = $athletes->pluck('id');
        $coverageService = app(AthleteSessionCoverageService::class);
        $adherenceValues = [];

        foreach ($athleteIds as $athleteId) {
            $assignment = AthleteProgramAssignment::query()
                ->where('athlete_id', $athleteId)
                ->where('status', 'active')
                ->whereDate('date_start', '<=', $today->toDateString())
                ->whereDate('date_end', '>=', $periodStart->toDateString())
                ->latest('date_start')
                ->first();

            if ($assignment === null) {
                continue;
            }

            $coverage = $coverageService->coverageBetween(
                $athleteId,
                $assignment,
                $periodStart,
                $today,
            );

            if ($coverage['percentage'] !== null) {
                $adherenceValues[] = $coverage['percentage'];
            }
        }

        $upcomingCompetitions = Competition::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('competition_date', '>=', $today->toDateString())
            ->count();

        $activeBlocks = AthleteProgramAssignment::query()
            ->whereIn('athlete_id', $athleteIds)
            ->where('status', 'active')
            ->whereDate('date_start', '<=', $today->toDateString())
            ->whereDate('date_end', '>=', $today->toDateString())
            ->count();

        return [
            'athlete_count' => $athleteIds->count(),
            'average_adherence_30d' => count($adherenceValues) > 0
                ? (int) round(array_sum($adherenceValues) / count($adherenceValues))
                : null,
            'upcoming_competitions' => $upcomingCompetitions,
            'active_blocks' => $activeBlocks,
        ];
    }
}
