<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\Competition;
use App\Models\PersonalRecord;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\FeedbackFrequencySupport;
use App\Support\ProgramSchedule;
use Carbon\CarbonInterface;

class AthleteDashboardPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forAthleteProgram(User $athlete, ?CarbonInterface $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();

        $activeAssignment = ActiveProgramAssignmentSupport::forAthleteOnDate($athlete, $date);

        return [
            'programBlock' => ProgramBlockPresenter::forAssignment($activeAssignment),
            'activeProgram' => self::activeProgramPayload($activeAssignment),
            'blockProgress' => self::blockProgress($activeAssignment, $date),
        ];
    }

    public static function forAthlete(User $athlete, ?CarbonInterface $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();
        $todayString = $date->toDateString();

        $athlete->loadMissing(['latestPr', 'upcomingCompetition']);

        $activeAssignment = ActiveProgramAssignmentSupport::forAthleteOnDate($athlete, $date);
        $programBlock = ProgramBlockPresenter::forAssignment($activeAssignment);
        $readiness = AthleteReadinessPresenter::forAthlete($athlete);
        $bodyWeight = AthleteBodyWeightPresenter::forAthlete($athlete);

        $latestPr = $athlete->latestPr;
        $blockProgress = self::blockProgress($activeAssignment, $date);
        $oneRm = self::oneRmPayload($programBlock, $latestPr);
        $todayLoggedSession = self::todayLoggedSession($athlete, $todayString);
        $feedbackDueToday = self::feedbackDueToday($athlete, $activeAssignment, $date);

        return [
            'athleteName' => $athlete->name,
            'athleteId' => $athlete->id,
            'todaySession' => AthleteTodaySessionPresenter::forAthlete($athlete, $date),
            'todayLoggedSession' => $todayLoggedSession,
            'todayReadiness' => $readiness['todayReadiness'],
            'readinessRecent' => $readiness['readinessRecent'],
            'todayBodyWeight' => $bodyWeight['todayBodyWeight'],
            'nextCompetition' => self::nextCompetitionPayload($athlete->upcomingCompetition, $date),
            'blockProgress' => $blockProgress,
            'oneRm' => $oneRm,
            'latestPr' => $latestPr ? [
                'squat' => (int) $latestPr->squat,
                'bench' => (int) $latestPr->bench,
                'deadlift' => (int) $latestPr->deadlift,
                'reference_date' => $latestPr->reference_date?->toDateString(),
                'gain_kg' => self::prGainSinceCoaching($athlete, $latestPr),
            ] : null,
            'feedbackFrequency' => FeedbackFrequencySupport::frequencyFor($athlete),
            'feedbackDueToday' => $feedbackDueToday,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function nextCompetitionPayload(?Competition $competition, CarbonInterface $date): ?array
    {
        if ($competition === null || $competition->competition_date === null) {
            return null;
        }

        $competitionDate = $competition->competition_date->copy()->startOfDay();
        $daysUntil = (int) $date->diffInDays($competitionDate);

        return [
            'id' => $competition->id,
            'name' => $competition->name,
            'competition_date' => $competitionDate->toDateString(),
            'goal' => $competition->goal,
            'location' => $competition->location,
            'days_until' => $daysUntil,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function activeProgramPayload(?AthleteProgramAssignment $assignment): ?array
    {
        if ($assignment === null) {
            return null;
        }

        $assignment->loadMissing('template.weeks');

        return [
            'id' => $assignment->id,
            'date_start' => $assignment->date_start?->toDateString(),
            'date_end' => $assignment->date_end?->toDateString(),
            'template' => [
                'id' => $assignment->template?->id,
                'name' => $assignment->template?->name,
                'weeks' => $assignment->template?->weeks
                    ?->sortBy('week_number')
                    ->values()
                    ->map(fn ($week) => [
                        'id' => $week->id,
                        'week_number' => $week->week_number,
                        'block_type' => $week->block_type,
                        'training_days' => $week->trainingDays
                            ?->sortBy('day_number')
                            ->values()
                            ->map(fn ($day) => [
                                'id' => $day->id,
                                'day_number' => $day->day_number,
                                'main_lift' => $day->main_lift,
                                'session_label' => $day->session_label,
                                'exercises' => $day->exercises
                                    ?->sortBy('sort_order')
                                    ->values()
                                    ->map(fn ($line) => [
                                        'id' => $line->id,
                                        'section' => $line->section,
                                        'sort_order' => $line->sort_order,
                                        'exercise_name' => $line->exercise_name,
                                        'lift' => $line->lift,
                                        'sets' => $line->sets,
                                        'reps' => $line->reps,
                                        'load' => $line->load,
                                        'load_percent' => $line->load_percent,
                                        'rpe' => $line->rpe,
                                    ])
                                    ->all() ?? [],
                            ])
                            ->all() ?? [],
                    ])
                    ->all() ?? [],
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function blockProgress(?AthleteProgramAssignment $assignment, CarbonInterface $date): ?array
    {
        if ($assignment === null) {
            return null;
        }

        $assignment->loadMissing('template.weeks');
        $weekCount = $assignment->template?->weeks?->count() ?? 0;
        $currentWeek = ProgramSchedule::weekForAssignmentOnDate($assignment, $date);

        return [
            'week_current' => $currentWeek?->week_number,
            'week_count' => $weekCount,
            'block_type' => $currentWeek?->block_type,
        ];
    }

    private static function prGainSinceCoaching(User $athlete, PersonalRecord $latest): int
    {
        $followUpStartedAt = $athlete->coaches()
            ->wherePivot('status', 'active')
            ->orderBy('coach_athlete.created_at')
            ->first()
            ?->pivot
            ?->created_at;

        $baselineQuery = $athlete->personalRecords()
            ->orderBy('reference_date')
            ->orderBy('id');

        $baseline = $followUpStartedAt
            ? $athlete->personalRecords()
                ->whereDate('reference_date', '>=', $followUpStartedAt->toDateString())
                ->orderBy('reference_date')
                ->orderBy('id')
                ->first()
            : null;

        $baseline ??= $baselineQuery->first();

        $latestTotal = (int) $latest->squat + (int) $latest->bench + (int) $latest->deadlift;

        if ($baseline === null) {
            return 0;
        }

        $baselineTotal = (int) $baseline->squat + (int) $baseline->bench + (int) $baseline->deadlift;

        return $latestTotal - $baselineTotal;
    }

    /**
     * @return array{squat: int, bench: int, deadlift: int}
     */
    private static function oneRmPayload(?array $programBlock, ?PersonalRecord $latestPr): array
    {
        $fromBlock = $programBlock['athlete_one_rm'] ?? null;

        if (is_array($fromBlock) && (($fromBlock['squat'] ?? 0) > 0 || ($fromBlock['bench'] ?? 0) > 0 || ($fromBlock['deadlift'] ?? 0) > 0)) {
            return [
                'squat' => (int) ($fromBlock['squat'] ?? 0),
                'bench' => (int) ($fromBlock['bench'] ?? 0),
                'deadlift' => (int) ($fromBlock['deadlift'] ?? 0),
            ];
        }

        return [
            'squat' => (int) ($latestPr?->squat ?? 0),
            'bench' => (int) ($latestPr?->bench ?? 0),
            'deadlift' => (int) ($latestPr?->deadlift ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function todayLoggedSession(User $athlete, string $dateString): ?array
    {
        $session = TrainingSession::query()
            ->where('athlete_id', $athlete->id)
            ->whereDate('session_date', $dateString)
            ->orderByDesc('id')
            ->first();

        return $session ? TrainingSessionSupport::toPayload($session) : null;
    }

    private static function feedbackDueToday(
        User $athlete,
        ?AthleteProgramAssignment $assignment,
        CarbonInterface $date,
    ): bool {
        if ($assignment === null) {
            return false;
        }

        if (FeedbackFrequencySupport::isWeekly($athlete)) {
            [$weekStart, $weekEnd] = FeedbackFrequencySupport::weekBounds($date);

            if (! ProgramSchedule::hasAnySessionBetween($assignment, $weekStart, $weekEnd)) {
                return false;
            }

            return ! FeedbackFrequencySupport::hasFeedbackForWeek($athlete, $weekStart);
        }

        if (! ProgramSchedule::hasSessionOnDate($assignment, $date)) {
            return false;
        }

        return ! FeedbackFrequencySupport::hasFeedbackForSessionDay($athlete, $date);
    }
}
