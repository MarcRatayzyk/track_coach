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
        $this->syncExpectations->execute($coach);

        $today = now()->copy()->startOfDay();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $this->linkOrphanWeeklyFeedbacks($coach, $weekStart, $weekEnd);

        $athleteIds = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->pluck('users.id');

        $dailyExpectedSlots = $this->countDailyExpectedSlots($coach, $athleteIds, $today);
        $weeklyExpectedSlots = $this->countWeeklyExpectedSlots($coach, $athleteIds, $weekStart, $weekEnd);

        $dailyTasksQuery = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('session_date')
            ->whereNull('period_week_start');

        $overdue = (clone $dailyTasksQuery)
            ->where('status', 'pending')
            ->whereDate('session_date', '<', $today->toDateString())
            ->count();

        $dueTodayPending = (clone $dailyTasksQuery)
            ->where('status', 'pending')
            ->whereDate('session_date', $today->toDateString())
            ->count();

        $dailyReceived = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereDate('session_date', $today->toDateString())
            ->count();

        $dailyProcessedToday = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->where('status', SessionFeedback::STATUS_COACH_REPLIED)
            ->whereHas('reply', fn ($q) => $q->whereDate('created_at', $today->toDateString()))
            ->count();

        $dailyPendingTasks = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('session_date')
            ->whereNull('period_week_start')
            ->where('status', 'pending')
            ->with('athlete:id,name')
            ->orderBy('session_date')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn (DashboardTask $task) => $this->presentTask($task));

        $weeklyTasksQuery = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('period_week_start')
            ->whereDate('period_week_start', $weekStart->toDateString());

        $weeklyReceived = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereDate('session_date', '>=', $weekStart->toDateString())
            ->whereDate('session_date', '<=', $weekEnd->toDateString())
            ->count();

        $weeklyProcessed = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->where('status', SessionFeedback::STATUS_COACH_REPLIED)
            ->whereDate('session_date', '>=', $weekStart->toDateString())
            ->whereDate('session_date', '<=', $weekEnd->toDateString())
            ->count();

        $weeklyPendingTasks = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereNotNull('period_week_start')
            ->whereDate('period_week_start', $weekStart->toDateString())
            ->where('status', 'pending')
            ->with('athlete:id,name')
            ->orderBy('due_at')
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn (DashboardTask $task) => $this->presentTask($task));

        $dailyExpectedToday = $overdue + $dailyExpectedSlots;

        return [
            'daily' => [
                'expected_today' => $dailyExpectedToday,
                'overdue' => $overdue,
                'due_today' => $dueTodayPending,
                'received_today' => $dailyReceived,
                'processed_today' => $dailyProcessedToday,
                'pending_tasks' => $dailyPendingTasks,
                'breakdown' => $this->buildDailyBreakdown($coach, $today),
            ],
            'weekly' => [
                'expected_week' => $weeklyExpectedSlots,
                'received_week' => $weeklyReceived,
                'processed_week' => $weeklyProcessed,
                'pending_tasks' => $weeklyPendingTasks,
                'breakdown' => $this->buildWeeklyBreakdown($coach, $weekStart, $weekEnd, $athleteIds),
            ],
            'week_start' => $weekStart->toDateString(),
            'week_end' => $weekEnd->toDateString(),
            'today' => $today->toDateString(),
        ];
    }

    private function countDailyExpectedSlots(User $coach, Collection $athleteIds, Carbon $today): int
    {
        if ($athleteIds->isEmpty()) {
            return 0;
        }

        $count = 0;
        $assignments = $this->activeAssignments($athleteIds, $today);

        foreach ($assignments as $assignment) {
            if (ProgramSchedule::hasSessionOnDate($assignment, $today)) {
                $count++;
            }
        }

        return $count;
    }

    private function countWeeklyExpectedSlots(User $coach, Collection $athleteIds, Carbon $weekStart, Carbon $weekEnd): int
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
        return AthleteProgramAssignment::query()
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
    private function buildDailyBreakdown(User $coach, Carbon $today): array
    {
        $todayString = $today->toDateString();

        $athleteIds = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->pluck('users.id');

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
            ->where('status', 'pending')
            ->with('athlete:id,name')
            ->orderBy('id')
            ->get();

        $pending = [];
        $submittedFromTasks = [];

        $linkedFeedbacks = SessionFeedback::query()
            ->whereIn('id', $pendingTasks->pluck('session_feedback_id')->filter())
            ->with('athlete:id,name')
            ->get()
            ->keyBy('id');

        foreach ($pendingTasks as $task) {
            if ($task->session_feedback_id !== null) {
                $feedback = $linkedFeedbacks->get($task->session_feedback_id);
                $submittedFromTasks[] = $feedback !== null
                    ? $this->presentBreakdownFromFeedback($feedback, $weekStartString)
                    : $this->presentBreakdownFromTask($task, $weekStartString);
            } else {
                $pending[] = $this->presentBreakdownFromTask($task, $weekStartString);
            }
        }

        $weekFeedbacks = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereDate('session_date', '>=', $weekStartString)
            ->whereDate('session_date', '<=', $weekEnd->toDateString())
            ->whereIn('athlete_id', $athleteIds)
            ->with('athlete:id,name')
            ->orderByDesc('submitted_at')
            ->get();

        $submittedAthleteIds = collect($submittedFromTasks)->pluck('athlete_id')->filter();

        foreach ($weekFeedbacks as $feedback) {
            if ($submittedAthleteIds->contains($feedback->athlete_id)) {
                continue;
            }
            $submittedFromTasks[] = $this->presentBreakdownFromFeedback($feedback, $weekStartString);
            $submittedAthleteIds->push($feedback->athlete_id);
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
        $isOverdue = $sessionDate !== null && $sessionDate < $referenceToday;

        return [
            'athlete_id' => $task->athlete_id,
            'athlete_name' => $task->athlete?->name,
            'session_date' => $sessionDate,
            'period_week_start' => $task->period_week_start?->toDateString(),
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
        ];
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
