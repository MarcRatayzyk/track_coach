<?php

namespace App\Services;

use App\Actions\SyncCoachFeedbackExpectations;
use App\Models\AthleteProfile;
use App\Models\AthleteProgramAssignment;
use App\Models\DashboardTask;
use App\Models\SessionFeedback;
use App\Models\User;
use App\Support\ProgramSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CoachFeedbackMetricsService
{
    /** @var array<string, Collection<int, AthleteProgramAssignment>> */
    private array $activeAssignmentsCache = [];

    private bool $expectationsSynced = false;

    public function __construct(
        private readonly SyncCoachFeedbackExpectations $syncExpectations,
    ) {}

    /**
     * @return array{
     *     daily: array<string, mixed>,
     *     weekly: array<string, mixed>,
     *     week_start: string,
     *     week_end: string,
     *     today: string,
     * }
     */
    public function forCoach(User $coach): array
    {
        $today = now()->copy()->startOfDay();

        if (! $this->expectationsSynced) {
            $this->syncExpectations->execute($coach, $today);
            $this->expectationsSynced = true;
        }

        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $this->linkOrphanWeeklyFeedbacks($coach, $weekStart, $weekEnd);
        $this->closeTasksAlreadyReplied($coach);

        $athleteIds = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->pluck('users.id');

        $dailyExpectedSlots = $this->countDailyExpectedSlots($athleteIds, $today);
        $dailyExpectedWeek = $this->countDailyExpectedSlotsInWeek($athleteIds, $weekStart, $weekEnd);
        $weeklyExpectedSlots = $this->countWeeklyExpectedSlots($athleteIds, $weekStart, $weekEnd);

        $dailyAthleteIds = $this->dailyAthleteIds($athleteIds, $today);

        $dailyTasksQuery = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('session_date')
            ->whereNull('period_week_start')
            ->whereIn('athlete_id', $dailyAthleteIds);

        // Attente athlète (pas un retard coach) — sert encore au dénominateur « attendus ».
        $awaitingAthlete = (clone $dailyTasksQuery)
            ->where('status', 'pending')
            ->whereDate('session_date', '<', $today->toDateString())
            ->whereNull('session_feedback_id')
            ->count();

        // Retard coach = retour déjà envoyé, non répondu, séance passée.
        $overdue = (clone $dailyTasksQuery)
            ->where('status', 'pending')
            ->whereDate('session_date', '<', $today->toDateString())
            ->whereNotNull('session_feedback_id')
            ->whereHas(
                'sessionFeedback',
                fn ($feedback) => $feedback->where(
                    'status',
                    '!=',
                    SessionFeedback::STATUS_COACH_REPLIED,
                ),
            )
            ->count();

        $dueTodayPending = (clone $dailyTasksQuery)
            ->where('status', 'pending')
            ->whereDate('session_date', $today->toDateString())
            ->whereNull('session_feedback_id')
            ->count();

        $dailyReceived = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereIn('athlete_id', $dailyAthleteIds)
            ->whereDate('session_date', $today->toDateString())
            ->count();

        $dailyRepliedToday = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereIn('athlete_id', $dailyAthleteIds)
            ->whereDate('session_date', $today->toDateString())
            ->where('status', SessionFeedback::STATUS_COACH_REPLIED)
            ->count();

        $dailyProcessedToday = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereIn('athlete_id', $dailyAthleteIds)
            ->where('status', SessionFeedback::STATUS_COACH_REPLIED)
            ->whereDate('updated_at', $today->toDateString())
            ->count();

        // Aujourd'hui (athlètes journaliers) + retards coach (tous athlètes) —
        // même logique que l'onglet Retours : session_date < today et pas répondu.
        $dailyPendingTasks = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('session_date')
            ->whereNull('period_week_start')
            ->where(function ($query) use ($today, $dailyAthleteIds, $athleteIds): void {
                $query->where(function ($todayQuery) use ($today, $dailyAthleteIds): void {
                    $todayQuery->whereIn('athlete_id', $dailyAthleteIds)
                        ->whereDate('session_date', $today->toDateString());
                })->orWhere(function ($overdueQuery) use ($today, $athleteIds): void {
                    $overdueQuery->whereIn('athlete_id', $athleteIds)
                        ->whereDate('session_date', '<', $today->toDateString())
                        ->where(function ($openQuery): void {
                            $openQuery->where(function ($waiting): void {
                                $waiting->where('status', 'pending')
                                    ->whereNull('session_feedback_id');
                            })->orWhereHas(
                                'sessionFeedback',
                                fn ($feedback) => $feedback->where(
                                    'status',
                                    '!=',
                                    SessionFeedback::STATUS_COACH_REPLIED,
                                ),
                            );
                        });
                });
            })
            ->with(['athlete:id,name', 'sessionFeedback:id,status'])
            ->orderBy('session_date')
            ->orderBy('id')
            ->limit(80)
            ->get()
            ->sortBy(fn (DashboardTask $task) => $this->feedbackListSortRank($task))
            ->values()
            ->map(fn (DashboardTask $task) => $this->presentTask($task));

        // Aligner le dashboard sur la page Retours : inclure les SessionFeedback
        // journaliers en retard même sans DashboardTask ouverte.
        $dailyPendingTasks = $this->mergeOrphanOverdueDailyFeedbacks(
            $dailyPendingTasks,
            $coach,
            $athleteIds,
            $today,
        );

        $weeklyCurrentQuery = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('period_week_start')
            ->whereDate('period_week_start', $weekStart->toDateString())
            ->whereIn('athlete_id', $athleteIds);

        $weeklyReceived = (clone $weeklyCurrentQuery)
            ->whereNotNull('session_feedback_id')
            ->count();

        $weeklyProcessed = (clone $weeklyCurrentQuery)
            ->whereNotNull('session_feedback_id')
            ->whereHas(
                'sessionFeedback',
                fn ($query) => $query->where('status', SessionFeedback::STATUS_COACH_REPLIED),
            )
            ->count();

        // Semaine courante + semaines passées encore ouvertes (vrai retard hebdo).
        $weeklyPendingTasks = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('period_week_start')
            ->whereIn('athlete_id', $athleteIds)
            ->where(function ($query) use ($weekStart): void {
                $query->whereDate('period_week_start', $weekStart->toDateString())
                    ->orWhere(function ($overdueQuery) use ($weekStart): void {
                        $overdueQuery->whereDate('period_week_start', '<', $weekStart->toDateString())
                            ->where(function ($openQuery): void {
                                $openQuery->where(function ($waiting): void {
                                    $waiting->where('status', 'pending')
                                        ->whereNull('session_feedback_id');
                                })->orWhereHas(
                                    'sessionFeedback',
                                    fn ($feedback) => $feedback->where(
                                        'status',
                                        '!=',
                                        SessionFeedback::STATUS_COACH_REPLIED,
                                    ),
                                );
                            });
                    });
            })
            ->with(['athlete:id,name', 'sessionFeedback:id,status'])
            ->orderBy('period_week_start')
            ->orderBy('id')
            ->limit(80)
            ->get()
            ->sortBy(fn (DashboardTask $task) => $this->feedbackListSortRank($task))
            ->values()
            ->map(fn (DashboardTask $task) => $this->presentTask($task));

        $dailyExpectedToday = $awaitingAthlete + $dailyExpectedSlots;

        return [
            'daily' => [
                'expected_today' => $dailyExpectedToday,
                'overdue' => $overdue,
                'awaiting_athlete' => $awaitingAthlete,
                'due_today' => $dueTodayPending,
                'received_today' => $dailyReceived,
                'replied_today' => $dailyRepliedToday,
                'processed_today' => $dailyProcessedToday,
                'pending_tasks' => $dailyPendingTasks,
                'breakdown' => $this->buildDailyBreakdown($coach, $today, $athleteIds),
            ],
            'weekly' => [
                'expected_week' => $weeklyExpectedSlots,
                'expected_week_daily' => $dailyExpectedWeek,
                'expected_week_total' => $dailyExpectedWeek + $weeklyExpectedSlots,
                'received_week' => $weeklyReceived,
                'processed_week' => $weeklyProcessed,
                'replied_week' => $weeklyProcessed,
                'pending_tasks' => $weeklyPendingTasks,
                'breakdown' => $this->buildWeeklyBreakdown($coach, $weekStart, $weekEnd, $athleteIds),
            ],
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'today' => $today->toDateString(),
        ];
    }

    private function countDailyExpectedSlots(Collection $athleteIds, Carbon $today): int
    {
        if ($athleteIds->isEmpty()) {
            return 0;
        }

        $count = 0;
        $assignments = $this->activeAssignments($athleteIds, $today);

        foreach ($assignments as $assignment) {
            $frequency = $assignment->athlete?->profile?->feedback_frequency
                ?? AthleteProfile::FREQUENCY_WEEKLY;

            if ($frequency !== AthleteProfile::FREQUENCY_DAILY) {
                continue;
            }

            if (ProgramSchedule::hasSessionOnDate($assignment, $today)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Nombre de retours journaliers attendus sur toute la semaine civile.
     */
    private function countDailyExpectedSlotsInWeek(Collection $athleteIds, Carbon $weekStart, Carbon $weekEnd): int
    {
        if ($athleteIds->isEmpty()) {
            return 0;
        }

        $count = 0;
        $assignments = $this->activeAssignments($athleteIds, $weekEnd);
        $cursor = $weekStart->copy()->startOfDay();
        $end = $weekEnd->copy()->startOfDay();

        while ($cursor->lte($end)) {
            foreach ($assignments as $assignment) {
                $frequency = $assignment->athlete?->profile?->feedback_frequency
                    ?? AthleteProfile::FREQUENCY_WEEKLY;

                if ($frequency !== AthleteProfile::FREQUENCY_DAILY) {
                    continue;
                }

                if (ProgramSchedule::hasSessionOnDate($assignment, $cursor)) {
                    $count++;
                }
            }

            $cursor->addDay();
        }

        return $count;
    }

    private function countWeeklyExpectedSlots(Collection $athleteIds, Carbon $weekStart, Carbon $weekEnd): int
    {
        if ($athleteIds->isEmpty()) {
            return 0;
        }

        $count = 0;
        $assignments = $this->activeAssignments($athleteIds, $weekEnd);

        foreach ($assignments as $assignment) {
            $frequency = $assignment->athlete?->profile?->feedback_frequency
                ?? AthleteProfile::FREQUENCY_WEEKLY;

            if ($frequency !== AthleteProfile::FREQUENCY_WEEKLY) {
                continue;
            }

            if (ProgramSchedule::hasAnySessionBetween($assignment, $weekStart, $weekEnd)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, AthleteProgramAssignment>
     */
    private function activeAssignments(Collection $athleteIds, Carbon $today): Collection
    {
        $cacheKey = $today->toDateString().':'.$athleteIds->sort()->implode(',');

        if (isset($this->activeAssignmentsCache[$cacheKey])) {
            return $this->activeAssignmentsCache[$cacheKey];
        }

        if ($athleteIds->isEmpty()) {
            return $this->activeAssignmentsCache[$cacheKey] = collect();
        }

        return $this->activeAssignmentsCache[$cacheKey] = AthleteProgramAssignment::query()
            ->whereIn('athlete_id', $athleteIds)
            ->where('status', 'active')
            ->whereDate('date_start', '<=', $today->toDateString())
            ->where(function ($query) use ($today): void {
                $query->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $today->toDateString());
            })
            ->with(['template.weeks.trainingDays', 'athlete.profile'])
            ->get();
    }

    /**
     * @param  Collection<int, int|string>  $athleteIds
     * @return list<int>
     */
    private function dailyAthleteIds(Collection $athleteIds, Carbon $today): array
    {
        if ($athleteIds->isEmpty()) {
            return [];
        }

        return $this->activeAssignments($athleteIds, $today)
            ->filter(function (AthleteProgramAssignment $assignment): bool {
                $frequency = $assignment->athlete?->profile?->feedback_frequency
                    ?? AthleteProfile::FREQUENCY_WEEKLY;

                return $frequency === AthleteProfile::FREQUENCY_DAILY;
            })
            ->pluck('athlete_id')
            ->unique()
            ->values()
            ->all();
    }

    private function activeAssignmentConstraint($query, Carbon $today): void
    {
        $query->where('status', 'active')
            ->whereDate('date_start', '<=', $today->toDateString())
            ->where(function ($inner) use ($today): void {
                $inner->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $today->toDateString());
            });
    }

    /**
     * @return array{pending: list<array<string, mixed>>, submitted: list<array<string, mixed>>}
     */
    private function buildDailyBreakdown(User $coach, Carbon $today, Collection $athleteIds): array
    {
        $todayString = $today->toDateString();

        $assignments = $this->activeAssignments($athleteIds, $today);

        $todayFeedbacks = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereDate('session_date', $todayString)
            ->with('athlete:id,name')
            ->get()
            ->keyBy('athlete_id');

        $pending = [];
        $submitted = [];

        foreach ($assignments as $assignment) {
            $frequency = $assignment->athlete?->profile?->feedback_frequency
                ?? AthleteProfile::FREQUENCY_WEEKLY;

            if ($frequency !== AthleteProfile::FREQUENCY_DAILY) {
                continue;
            }

            if (! ProgramSchedule::hasSessionOnDate($assignment, $today)) {
                continue;
            }

            $athlete = $assignment->athlete;
            $feedback = $todayFeedbacks->get($assignment->athlete_id);

            if ($feedback !== null) {
                $submitted[] = $this->presentBreakdownFromFeedback($feedback, $todayString);
            } else {
                $pending[] = [
                    'athlete_id' => $assignment->athlete_id,
                    'athlete_name' => $athlete?->name,
                    'session_date' => $todayString,
                    'period_week_start' => null,
                    'session_feedback_id' => null,
                    'feedback_status' => null,
                    'is_overdue' => false,
                ];
            }
        }

        $pendingKeys = collect($pending)->map(
            fn (array $row) => "{$row['athlete_id']}-{$row['session_date']}",
        )->flip();

        $overdueTasks = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('session_date')
            ->whereNull('period_week_start')
            ->where('status', 'pending')
            ->whereDate('session_date', '<', $todayString)
            ->whereNull('session_feedback_id')
            ->with('athlete:id,name')
            ->orderBy('session_date')
            ->get();

        foreach ($overdueTasks as $task) {
            $key = "{$task->athlete_id}-{$task->session_date?->toDateString()}";
            if ($pendingKeys->has($key)) {
                continue;
            }
            $pending[] = $this->presentBreakdownFromTask($task, $todayString);
        }

        $overdueSubmittedTasks = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('session_date')
            ->whereNull('period_week_start')
            ->where('status', 'pending')
            ->whereDate('session_date', '<', $todayString)
            ->whereNotNull('session_feedback_id')
            ->with('athlete:id,name')
            ->orderBy('session_date')
            ->get();

        $overdueFeedbackIds = $overdueSubmittedTasks->pluck('session_feedback_id')->filter();
        $overdueFeedbacks = SessionFeedback::query()
            ->whereIn('id', $overdueFeedbackIds)
            ->with('athlete:id,name')
            ->get()
            ->keyBy('id');

        foreach ($overdueSubmittedTasks as $task) {
            $feedback = $overdueFeedbacks->get($task->session_feedback_id);
            if ($feedback !== null) {
                $submitted[] = $this->presentBreakdownFromFeedback($feedback, $todayString);
            }
        }

        return [
            'pending' => array_values($pending),
            'submitted' => $submitted,
        ];
    }

    /**
     * @return array{pending: list<array<string, mixed>>, submitted: list<array<string, mixed>>}
     */
    private function buildWeeklyBreakdown(User $coach, Carbon $weekStart, Carbon $weekEnd, Collection $athleteIds): array
    {
        $weekStartString = $weekStart->toDateString();
        $assignments = $this->activeAssignments($athleteIds, $weekEnd);

        $pendingTasks = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('period_week_start')
            ->whereDate('period_week_start', $weekStartString)
            ->with(['athlete:id,name', 'sessionFeedback.athlete:id,name'])
            ->orderBy('id')
            ->get();

        $pending = [];
        $submittedFromTasks = [];

        foreach ($pendingTasks as $task) {
            if ($task->session_feedback_id !== null) {
                $feedback = $task->sessionFeedback;
                $submittedFromTasks[] = $feedback !== null
                    ? $this->presentBreakdownFromFeedback($feedback, $weekStartString)
                    : $this->presentBreakdownFromTask($task, $weekStartString);
            } else {
                $pending[] = $this->presentBreakdownFromTask($task, $weekStartString);
            }
        }

        $weeklyAthleteIds = collect($pending)->pluck('athlete_id')
            ->merge(collect($submittedFromTasks)->pluck('athlete_id'))
            ->unique();

        foreach ($assignments as $assignment) {
            $frequency = $assignment->athlete?->profile?->feedback_frequency
                ?? AthleteProfile::FREQUENCY_WEEKLY;

            if ($frequency !== AthleteProfile::FREQUENCY_WEEKLY) {
                continue;
            }

            if (! ProgramSchedule::hasAnySessionBetween($assignment, $weekStart, $weekEnd)) {
                continue;
            }

            if ($weeklyAthleteIds->contains($assignment->athlete_id)) {
                continue;
            }

            $pending[] = [
                'athlete_id' => $assignment->athlete_id,
                'athlete_name' => $assignment->athlete?->name,
                'session_date' => null,
                'period_week_start' => $weekStartString,
                'session_feedback_id' => null,
                'feedback_status' => null,
                'is_overdue' => false,
            ];
        }

        return [
            'pending' => $pending,
            'submitted' => $submittedFromTasks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentBreakdownFromTask(DashboardTask $task, string $referenceToday): array
    {
        $sessionDate = $task->session_date?->toDateString();
        $periodWeekStart = $task->period_week_start?->toDateString();

        // Sans soumission : pas un retard coach (signal via alertes).
        $isOverdue = false;
        if ($task->session_feedback_id !== null) {
            if ($periodWeekStart !== null) {
                $weekEnd = Carbon::parse($periodWeekStart)
                    ->startOfWeek(Carbon::MONDAY)
                    ->endOfWeek(Carbon::SUNDAY)
                    ->toDateString();
                $isOverdue = $referenceToday > $weekEnd;
            } else {
                $isOverdue = $sessionDate !== null && $sessionDate < $referenceToday;
            }
        }

        return [
            'athlete_id' => $task->athlete_id,
            'athlete_name' => $task->athlete?->name,
            'session_date' => $sessionDate,
            'period_week_start' => $periodWeekStart,
            'session_feedback_id' => $task->session_feedback_id,
            'feedback_status' => null,
            'is_overdue' => $isOverdue,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentBreakdownFromFeedback(SessionFeedback $feedback, string $referenceToday): array
    {
        $sessionDate = $feedback->session_date?->toDateString();

        return [
            'athlete_id' => $feedback->athlete_id,
            'athlete_name' => $feedback->athlete?->name,
            'session_date' => $sessionDate,
            'period_week_start' => null,
            'session_feedback_id' => $feedback->id,
            'feedback_status' => $feedback->status,
            'is_overdue' => $sessionDate !== null && $sessionDate < $referenceToday,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentTask(DashboardTask $task): array
    {
        return [
            'id' => $task->id,
            'athlete_id' => $task->athlete_id,
            'athlete' => $task->athlete ? ['id' => $task->athlete->id, 'name' => $task->athlete->name] : null,
            'session_date' => $task->session_date?->toDateString(),
            'period_week_start' => $task->period_week_start?->toDateString(),
            'due_at' => $task->due_at?->toIso8601String(),
            'status' => $task->status,
            'session_feedback_id' => $task->session_feedback_id,
            'has_submission' => $task->session_feedback_id !== null,
            'feedback_status' => $task->sessionFeedback?->status,
        ];
    }

    /**
     * Ordre d'affichage dashboard : pas encore envoyé → reçu → répondu.
     */
    private function feedbackListSortRank(DashboardTask $task): int
    {
        if ($task->sessionFeedback?->status === SessionFeedback::STATUS_COACH_REPLIED) {
            return 2;
        }

        if ($task->session_feedback_id !== null) {
            return 1;
        }

        return 0;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $tasks
     * @param  Collection<int, int|string>  $athleteIds
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function mergeOrphanOverdueDailyFeedbacks(
        $tasks,
        User $coach,
        Collection $athleteIds,
        Carbon $today,
    ) {
        if ($athleteIds->isEmpty()) {
            return $tasks;
        }

        $existingFeedbackIds = $tasks
            ->pluck('session_feedback_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->all();

        $orphans = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereIn('athlete_id', $athleteIds)
            ->where('status', '!=', SessionFeedback::STATUS_COACH_REPLIED)
            ->whereDate('session_date', '<', $today->toDateString())
            ->whereHas('athlete.profile', function ($query): void {
                $query->where('feedback_frequency', AthleteProfile::FREQUENCY_DAILY);
            })
            ->when(
                $existingFeedbackIds !== [],
                fn ($query) => $query->whereNotIn('id', $existingFeedbackIds),
            )
            ->with(['athlete:id,name'])
            ->orderBy('session_date')
            ->limit(40)
            ->get();

        if ($orphans->isEmpty()) {
            return $tasks;
        }

        $extra = $orphans->map(fn (SessionFeedback $feedback) => [
            'id' => 'sf-'.$feedback->id,
            'athlete_id' => $feedback->athlete_id,
            'athlete' => $feedback->athlete
                ? ['id' => $feedback->athlete->id, 'name' => $feedback->athlete->name]
                : null,
            'session_date' => $feedback->session_date?->toDateString(),
            'period_week_start' => null,
            'due_at' => $feedback->session_date?->copy()->endOfDay()?->toIso8601String(),
            'status' => 'pending',
            'session_feedback_id' => $feedback->id,
            'has_submission' => true,
            'feedback_status' => $feedback->status,
            'feedback_frequency' => AthleteProfile::FREQUENCY_DAILY,
        ]);

        return $tasks->concat($extra)->values();
    }

    private function closeTasksAlreadyReplied(User $coach): void
    {
        DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->where('status', 'pending')
            ->whereNotNull('session_feedback_id')
            ->whereHas(
                'sessionFeedback',
                fn ($query) => $query->where('status', SessionFeedback::STATUS_COACH_REPLIED),
            )
            ->update([
                'status' => 'done',
                'completed_at' => now(),
            ]);
    }

    private function linkOrphanWeeklyFeedbacks(User $coach, Carbon $weekStart, Carbon $weekEnd): void
    {
        $tasks = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('period_week_start')
            ->whereDate('period_week_start', $weekStart->toDateString())
            ->where('status', 'pending')
            ->whereNull('session_feedback_id')
            ->get();

        if ($tasks->isEmpty()) {
            return;
        }

        $feedbacksByAthlete = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->where('status', SessionFeedback::STATUS_SUBMITTED)
            ->whereIn('athlete_id', $tasks->pluck('athlete_id'))
            ->whereDate('session_date', '>=', $weekStart->toDateString())
            ->whereDate('session_date', '<=', $weekEnd->toDateString())
            ->orderByDesc('submitted_at')
            ->get()
            ->groupBy('athlete_id');

        foreach ($tasks as $task) {
            $feedback = $feedbacksByAthlete->get($task->athlete_id)?->first();
            if ($feedback !== null) {
                $task->update(['session_feedback_id' => $feedback->id]);
            }
        }
    }
}
