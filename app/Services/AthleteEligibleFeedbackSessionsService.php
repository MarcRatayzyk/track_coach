<?php

namespace App\Services;

use App\Models\SessionFeedback;
use App\Models\User;
use App\Support\ProgramSchedule;
use App\Support\SessionFeedbackPresenter;
use Carbon\Carbon;

class AthleteEligibleFeedbackSessionsService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forAthlete(User $athlete, int $daysBack = 14): array
    {
        $assignment = $athlete->programAssignments()
            ->where('status', 'active')
            ->whereDate('date_start', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', now()->toDateString());
            })
            ->with('template.weeks.trainingDays')
            ->latest('date_start')
            ->first();

        if ($assignment === null) {
            return [];
        }

        $submittedDates = SessionFeedback::query()
            ->where('athlete_id', $athlete->id)
            ->pluck('session_date')
            ->map(fn ($d) => $d->toDateString())
            ->flip();

        $eligible = [];
        $today = now()->startOfDay();

        for ($i = 0; $i <= $daysBack; $i++) {
            $date = $today->copy()->subDays($i);
            $dateString = $date->toDateString();

            if (isset($submittedDates[$dateString])) {
                continue;
            }

            $trainingDay = ProgramSchedule::resolveTrainingDayForDate($assignment, $date);
            if ($trainingDay === null) {
                continue;
            }

            $eligible[] = [
                'session_date' => $dateString,
                'program_training_day_id' => $trainingDay->id,
                'session_label' => SessionFeedbackPresenter::sessionLabel($trainingDay),
            ];
        }

        return $eligible;
    }
}
