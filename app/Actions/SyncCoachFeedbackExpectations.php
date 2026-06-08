<?php

namespace App\Actions;

use App\Models\AthleteProfile;
use App\Models\AthleteProgramAssignment;
use App\Models\DashboardTask;
use App\Models\User;
use App\Support\ProgramSchedule;
use Carbon\Carbon;

class SyncCoachFeedbackExpectations
{
    public function execute(User $coach, ?Carbon $referenceDate = null): void
    {
        $today = ($referenceDate ?? now())->copy()->startOfDay();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $athletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->with([
                'profile',
                'programAssignments' => fn ($query) => $query
                    ->where('status', 'active')
                    ->whereDate('date_start', '<=', $today->toDateString())
                    ->where(function ($query) use ($today): void {
                        $query->whereNull('date_end')
                            ->orWhereDate('date_end', '>=', $today->toDateString());
                    })
                    ->with('template.weeks.trainingDays'),
            ])
            ->get();

        foreach ($athletes as $athlete) {
            $assignment = $athlete->programAssignments->first();
            if ($assignment === null) {
                continue;
            }

            $frequency = $athlete->profile?->feedback_frequency
                ?? AthleteProfile::FREQUENCY_WEEKLY;

            if (ProgramSchedule::hasSessionOnDate($assignment, $today)) {
                $this->syncSessionDayExpectation($coach, $athlete, $today);
            }

            if (
                $frequency === AthleteProfile::FREQUENCY_WEEKLY
                && ProgramSchedule::hasAnySessionBetween($assignment, $weekStart, $weekEnd)
            ) {
                $this->syncWeeklyExpectation($coach, $athlete, $weekStart, $weekEnd);
            }
        }
    }

    private function syncSessionDayExpectation(User $coach, User $athlete, Carbon $today): void
    {
        $sessionDate = $today->toDateString();

        $existing = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('athlete_id', $athlete->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereDate('session_date', $sessionDate)
            ->first();

        if ($existing !== null) {
            return;
        }

        DashboardTask::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'type' => DashboardTask::TYPE_FEEDBACK_SESSION,
            'session_date' => $sessionDate,
            'period_week_start' => null,
            'due_at' => $today->copy()->endOfDay(),
            'status' => 'pending',
        ]);
    }

    private function syncWeeklyExpectation(
        User $coach,
        User $athlete,
        Carbon $weekStart,
        Carbon $weekEnd,
    ): void {
        $weekStartDate = $weekStart->toDateString();

        $existing = DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->where('athlete_id', $athlete->id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->whereDate('period_week_start', $weekStartDate)
            ->first();

        if ($existing !== null) {
            return;
        }

        DashboardTask::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'type' => DashboardTask::TYPE_FEEDBACK_SESSION,
            'session_date' => null,
            'period_week_start' => $weekStartDate,
            'due_at' => $weekEnd,
            'status' => 'pending',
        ]);
    }
}
