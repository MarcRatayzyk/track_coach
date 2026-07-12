<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AthleteSessionCoverageService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AthleteWrappedPresenter
{
  private const LIFTS = [
        'squat' => 'Squat',
        'bench' => 'Bench',
        'deadlift' => 'Terre',
    ];

    public function __construct(
        private readonly AthleteSessionCoverageService $sessionCoverage,
    ) {}

    /**
     * @return array{weekly: ?array<string, mixed>, monthly: ?array<string, mixed>}
     */
    public function forAthlete(User $athlete, ?AthleteProgramAssignment $assignment, CarbonInterface $date): array
    {
        if ($assignment === null) {
            return ['weekly' => null, 'monthly' => null];
        }

        $today = $date->copy()->startOfDay();

        return [
            'weekly' => $this->weeklyWrapped($athlete, $assignment, $today),
            'monthly' => $this->monthlyWrapped($athlete, $assignment, $today),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function weeklyWrapped(User $athlete, AthleteProgramAssignment $assignment, CarbonInterface $today): ?array
    {
        [$start, $end] = FeedbackFrequencySupport::weekBounds($today);

        if (! $this->allPlannedSessionsCompleted($athlete->id, $assignment, $start, $end)) {
            return null;
        }

        [$prevStart, $prevEnd] = $this->previousWeekBounds($start);

        return $this->buildWrappedPayload(
            athlete: $athlete,
            assignment: $assignment,
            start: $start,
            end: $end,
            previousStart: $prevStart,
            previousEnd: $prevEnd,
            label: 'Weekly Wrapped',
            variant: 'weekly_wrapped',
            comparisonLabel: 'la semaine précédente',
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function monthlyWrapped(User $athlete, AthleteProgramAssignment $assignment, CarbonInterface $today): ?array
    {
        $start = $today->copy()->startOfMonth()->startOfDay();
        $end = $today->copy()->endOfMonth()->startOfDay();

        if (! $this->allPlannedSessionsCompleted($athlete->id, $assignment, $start, $end)) {
            return null;
        }

        $lastPlannedDate = $this->lastPlannedSessionDate($assignment, $start, $end);

        if ($lastPlannedDate === null || $today->lte($lastPlannedDate)) {
            return null;
        }

        $prevStart = $start->copy()->subMonth()->startOfMonth()->startOfDay();
        $prevEnd = $start->copy()->subMonth()->endOfMonth()->startOfDay();

        return $this->buildWrappedPayload(
            athlete: $athlete,
            assignment: $assignment,
            start: $start,
            end: $end,
            previousStart: $prevStart,
            previousEnd: $prevEnd,
            label: 'Monthly Wrapped',
            variant: 'monthly_wrapped',
            comparisonLabel: 'le mois précédent',
        );
    }

    private function allPlannedSessionsCompleted(
        int $athleteId,
        AthleteProgramAssignment $assignment,
        CarbonInterface $start,
        CarbonInterface $end,
    ): bool {
        $coverage = $this->sessionCoverage->coverageBetween($athleteId, $assignment, $start, $end);

        return ($coverage['planned'] ?? 0) > 0
            && ($coverage['completed'] ?? 0) >= ($coverage['planned'] ?? 0);
    }

    private function lastPlannedSessionDate(
        AthleteProgramAssignment $assignment,
        CarbonInterface $start,
        CarbonInterface $end,
    ): ?CarbonInterface {
        $last = null;
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if (ProgramSchedule::hasSessionOnDate($assignment, $cursor)) {
                $last = $cursor->copy();
            }

            $cursor = $cursor->copy()->addDay();
        }

        return $last;
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface}
     */
    private function previousWeekBounds(CarbonInterface $weekStart): array
    {
        $prevStart = $weekStart->copy()->subWeek()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $prevEnd = $prevStart->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay();

        return [$prevStart, $prevEnd];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildWrappedPayload(
        User $athlete,
        AthleteProgramAssignment $assignment,
        CarbonInterface $start,
        CarbonInterface $end,
        CarbonInterface $previousStart,
        CarbonInterface $previousEnd,
        string $label,
        string $variant,
        string $comparisonLabel,
    ): array {
        $sessions = $this->sessionsBetween($athlete->id, $start, $end);
        $previousSessions = $this->sessionsBetween($athlete->id, $previousStart, $previousEnd);

        $currentStats = $this->aggregateStats($sessions);
        $previousStats = $this->aggregateStats($previousSessions);

        $adherence = $this->sessionCoverage->coverageBetween(
            $athlete->id,
            $assignment,
            $start,
            $end,
            $sessions,
        )['percentage'];

        $currentStats['adherence_percent'] = $adherence;
        $previousStats['adherence_percent'] = $this->sessionCoverage->coverageBetween(
            $athlete->id,
            $assignment,
            $previousStart,
            $previousEnd,
            $previousSessions,
        )['percentage'];

        $overview = $this->overviewPayload($currentStats, $previousStats);
        $lifts = $this->liftsPayload($currentStats['lifts'], $previousStats['lifts']);

        $shareText = "{$athlete->name} · {$label} : {$currentStats['total_reps']} reps, {$currentStats['total_sets']} séries, "
            ."{$currentStats['total_tonnage']} kg de tonnage"
            .($adherence !== null ? ", adhérence {$adherence}%" : '');

        return [
            'label' => $label,
            'variant' => $variant,
            'comparison_label' => $comparisonLabel,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'session_count' => $sessions->count(),
            'overview' => $overview,
            'lifts' => $lifts,
            'share_payload' => [
                'variant' => $variant,
                'athlete_name' => $athlete->name,
                'date' => $end->toDateString(),
                'headline' => $label,
                'subline' => "{$currentStats['total_reps']} reps · {$currentStats['total_sets']} séries · {$currentStats['total_tonnage']} kg",
                'social_text' => $shareText,
                'share_url' => '/athlete/dashboard',
            ],
        ];
    }

    /**
     * @return Collection<int, TrainingSession>
     */
    private function sessionsBetween(int $athleteId, CarbonInterface $start, CarbonInterface $end): Collection
    {
        return TrainingSession::query()
            ->where('athlete_id', $athleteId)
            ->whereDate('session_date', '>=', $start->toDateString())
            ->whereDate('session_date', '<=', $end->toDateString())
            ->orderBy('session_date')
            ->get();
    }

    /**
     * @param  Collection<int, TrainingSession>  $sessions
     * @return array{
     *     total_sets: int,
     *     total_reps: int,
     *     total_tonnage: int,
     *     lifts: array<string, array{heaviest_bar: float, top_e1rm: float, tonnage: float}>
     * }
     */
    private function aggregateStats(Collection $sessions): array
    {
        $totalSets = 0;
        $totalReps = 0;
        $totalTonnage = 0.0;
        $lifts = $this->emptyLiftStats();

        foreach ($sessions as $session) {
            $mainLift = $session->main_lift ?? 'squat';

            foreach (($session->items ?? []) as $item) {
                $sets = (int) ($item['sets'] ?? 0);
                $reps = (int) ($item['reps'] ?? 0);
                $load = (float) ($item['load'] ?? 0);

                if ($sets <= 0 || $reps <= 0) {
                    continue;
                }

                $totalSets += $sets;
                $totalReps += ($sets * $reps);

                if ($load > 0) {
                    $lineTonnage = $sets * $reps * $load;
                    $totalTonnage += $lineTonnage;

                    $lift = $item['lift'] ?? $mainLift;
                    if (! isset($lifts[$lift])) {
                        continue;
                    }

                    $lifts[$lift]['heaviest_bar'] = max($lifts[$lift]['heaviest_bar'], $load);
                    $lifts[$lift]['tonnage'] += $lineTonnage;

                    $e1rm = $this->epleyE1rm($load, $reps);
                    if ($e1rm !== null) {
                        $lifts[$lift]['top_e1rm'] = max($lifts[$lift]['top_e1rm'], $e1rm);
                    }
                }
            }
        }

        return [
            'total_sets' => $totalSets,
            'total_reps' => $totalReps,
            'total_tonnage' => (int) round($totalTonnage),
            'lifts' => $lifts,
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $previous
     * @return array<string, mixed>
     */
    private function overviewPayload(array $current, array $previous): array
    {
        $tonnagePerSet = $current['total_sets'] > 0
            ? round($current['total_tonnage'] / $current['total_sets'], 1)
            : null;
        $prevTonnagePerSet = ($previous['total_sets'] ?? 0) > 0
            ? round(($previous['total_tonnage'] ?? 0) / $previous['total_sets'], 1)
            : null;

        return [
            'total_sets' => [
                'value' => $current['total_sets'],
                'delta' => $this->delta($current['total_sets'], $previous['total_sets'] ?? 0),
            ],
            'total_reps' => [
                'value' => $current['total_reps'],
                'delta' => $this->delta($current['total_reps'], $previous['total_reps'] ?? 0),
            ],
            'total_tonnage' => [
                'value' => $current['total_tonnage'],
                'delta' => $this->delta($current['total_tonnage'], $previous['total_tonnage'] ?? 0),
            ],
            'adherence_percent' => [
                'value' => $current['adherence_percent'] ?? null,
                'delta' => $this->delta(
                    $current['adherence_percent'] ?? null,
                    $previous['adherence_percent'] ?? null,
                ),
            ],
            'tonnage_per_set' => [
                'value' => $tonnagePerSet,
                'delta' => $this->delta($tonnagePerSet, $prevTonnagePerSet),
            ],
        ];
    }

    /**
     * @param  array<string, array{heaviest_bar: float, top_e1rm: float, tonnage: float}>  $current
     * @param  array<string, array{heaviest_bar: float, top_e1rm: float, tonnage: float}>  $previous
     * @return list<array<string, mixed>>
     */
    private function liftsPayload(array $current, array $previous): array
    {
        $payload = [];

        foreach (self::LIFTS as $key => $label) {
            $cur = $current[$key] ?? $this->emptyLiftStat();
            $prev = $previous[$key] ?? $this->emptyLiftStat();

            $payload[] = [
                'key' => $key,
                'label' => $label,
                'heaviest_bar' => [
                    'value' => $this->roundOrNull($cur['heaviest_bar']),
                    'delta' => $this->delta($cur['heaviest_bar'], $prev['heaviest_bar']),
                ],
                'top_e1rm' => [
                    'value' => $this->roundOrNull($cur['top_e1rm'], 1),
                    'delta' => $this->delta($cur['top_e1rm'], $prev['top_e1rm']),
                ],
                'tonnage' => [
                    'value' => (int) round($cur['tonnage']),
                    'delta' => $this->delta($cur['tonnage'], $prev['tonnage']),
                ],
            ];
        }

        return $payload;
    }

    /**
     * @return array<string, array{heaviest_bar: float, top_e1rm: float, tonnage: float}>
     */
    private function emptyLiftStats(): array
    {
        return [
            'squat' => $this->emptyLiftStat(),
            'bench' => $this->emptyLiftStat(),
            'deadlift' => $this->emptyLiftStat(),
        ];
    }

    /**
     * @return array{heaviest_bar: float, top_e1rm: float, tonnage: float}
     */
    private function emptyLiftStat(): array
    {
        return [
            'heaviest_bar' => 0.0,
            'top_e1rm' => 0.0,
            'tonnage' => 0.0,
        ];
    }

    private function epleyE1rm(float $load, int $reps): ?float
    {
        if ($load <= 0 || $reps <= 0) {
            return null;
        }

        return $load * (1 + ($reps / 30));
    }

    /**
     * @return array{absolute: float|int|null, percent: int|null, direction: string}|null
     */
    private function delta(float|int|null $current, float|int|null $previous): ?array
    {
        if ($current === null && $previous === null) {
            return null;
        }

        $cur = (float) ($current ?? 0);
        $prev = (float) ($previous ?? 0);
        $absolute = $cur - $prev;

        $percent = null;
        if ($prev > 0) {
            $percent = (int) round(($absolute / $prev) * 100);
        } elseif ($cur > 0) {
            $percent = 100;
        } else {
            $percent = 0;
        }

        $direction = $absolute > 0 ? 'up' : ($absolute < 0 ? 'down' : 'flat');

        return [
            'absolute' => abs($absolute) < 1 ? round($absolute, 1) : (int) round($absolute),
            'percent' => $percent,
            'direction' => $direction,
        ];
    }

    private function roundOrNull(float $value, int $precision = 0): ?float
    {
        if ($value <= 0) {
            return null;
        }

        return round($value, $precision);
    }
}
