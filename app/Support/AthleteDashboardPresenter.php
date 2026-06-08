<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\Competition;
use App\Models\MessageThread;
use App\Models\PersonalRecord;
use App\Models\SessionFeedback;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\CarbonInterface;

class AthleteDashboardPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forAthleteProgram(User $athlete, ?CarbonInterface $date = null): array
    {
        $date = ($date ?? now())->copy()->startOfDay();

        $activeAssignment = self::activeAssignment($athlete, $date);

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

        $activeAssignment = self::activeAssignment($athlete, $date);
        $programBlock = ProgramBlockPresenter::forAssignment($activeAssignment);
        $readiness = AthleteReadinessPresenter::forAthlete($athlete);

        $trainingSessions = $athlete->trainingSessions()
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->limit(60)
            ->get()
            ->map(fn ($session) => TrainingSessionSupport::toPayload($session))
            ->values()
            ->all();

        $latestPr = $athlete->latestPr;
        $blockProgress = self::blockProgress($activeAssignment, $date);
        $oneRm = self::oneRmPayload($programBlock, $latestPr);
        $todayLoggedSession = self::todayLoggedSession($athlete, $todayString);
        $feedbackDueToday = self::feedbackDueToday($athlete, $activeAssignment, $date);

        $recentFeedbacks = SessionFeedback::query()
            ->where('athlete_id', $athlete->id)
            ->with(['programTrainingDay', 'athleteVideos', 'reply'])
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->limit(3)
            ->get();

        $pendingReplyCount = SessionFeedback::query()
            ->where('athlete_id', $athlete->id)
            ->where('status', SessionFeedback::STATUS_SUBMITTED)
            ->count();

        $personalRecords = $athlete->personalRecords()
            ->orderByDesc('reference_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn (PersonalRecord $record) => [
                'squat' => (int) $record->squat,
                'bench' => (int) $record->bench,
                'deadlift' => (int) $record->deadlift,
                'reference_date' => $record->reference_date?->toDateString(),
            ])
            ->values()
            ->all();

        return [
            'athleteName' => $athlete->name,
            'athleteId' => $athlete->id,
            'todaySession' => AthleteTodaySessionPresenter::forAthlete($athlete, $date),
            'todayLoggedSession' => $todayLoggedSession,
            'todayReadiness' => $readiness['todayReadiness'],
            'readinessRecent' => $readiness['readinessRecent'],
            'nextCompetition' => self::nextCompetitionPayload($athlete->upcomingCompetition, $date),
            'programBlock' => $programBlock,
            'activeProgram' => self::activeProgramPayload($activeAssignment),
            'blockProgress' => $blockProgress,
            'trainingSessions' => $trainingSessions,
            'oneRm' => $oneRm,
            'latestPr' => $latestPr ? [
                'squat' => (int) $latestPr->squat,
                'bench' => (int) $latestPr->bench,
                'deadlift' => (int) $latestPr->deadlift,
                'reference_date' => $latestPr->reference_date?->toDateString(),
                'gain_kg' => self::prGainSinceCoaching($athlete, $latestPr),
            ] : null,
            'personalRecords' => $personalRecords,
            'recentFeedbacks' => SessionFeedbackPresenter::list($recentFeedbacks),
            'feedbackSummary' => [
                'pending_reply' => $pendingReplyCount,
                'due_today' => $feedbackDueToday,
            ],
            'coachThread' => self::coachThreadPayload($athlete),
            'feedbackDueToday' => $feedbackDueToday,
        ];
    }

    private static function activeAssignment(User $athlete, CarbonInterface $date): ?AthleteProgramAssignment
    {
        return $athlete->programAssignments()
            ->where('status', 'active')
            ->whereDate('date_start', '<=', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $date->toDateString());
            })
            ->with(['template.weeks.trainingDays.exercises'])
            ->latest('date_start')
            ->first();
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

    /**
     * @return array<string, mixed>|null
     */
    private static function coachThreadPayload(User $athlete): ?array
    {
        $coach = $athlete->coaches()
            ->wherePivot('status', 'active')
            ->orderBy('coach_athlete.created_at')
            ->first();

        if ($coach === null) {
            return null;
        }

        $thread = MessageThread::query()->firstOrCreate([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);

        $thread->loadMissing('coach:id,name');
        $thread->loadCount([
            'messages as unread_messages_count' => fn ($query) => $query
                ->whereNull('read_at')
                ->where('sender_id', '!=', $athlete->id),
        ]);

        $lastMessage = $thread->messages()
            ->with('sender:id,name')
            ->orderByDesc('created_at')
            ->first();

        return [
            'id' => $thread->id,
            'coach_id' => $coach->id,
            'coach_name' => $coach->name,
            'unread_count' => (int) ($thread->unread_messages_count ?? 0),
            'last_message' => $lastMessage ? [
                'content' => $lastMessage->content,
                'sender_name' => $lastMessage->sender?->name,
                'created_at' => $lastMessage->created_at?->toIso8601String(),
                'is_mine' => $lastMessage->sender_id === $athlete->id,
            ] : null,
        ];
    }

    private static function feedbackDueToday(
        User $athlete,
        ?AthleteProgramAssignment $assignment,
        CarbonInterface $date,
    ): bool {
        if ($assignment === null) {
            return false;
        }

        if (! ProgramSchedule::hasSessionOnDate($assignment, $date)) {
            return false;
        }

        return ! SessionFeedback::query()
            ->where('athlete_id', $athlete->id)
            ->whereDate('session_date', $date->toDateString())
            ->exists();
    }
}
