<?php

namespace App\Services;

use App\Models\ProgramDayExercise;
use App\Models\ProgramTrainingDay;
use App\Models\SessionFeedback;
use App\Models\User;
use App\Support\FeedbackFrequencySupport;
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

        if (FeedbackFrequencySupport::isWeekly($athlete)) {
            return $this->weeklyEligibleSessions($athlete, $assignment, $daysBack);
        }

        return $this->dailyEligibleSessions($athlete, $assignment, $daysBack);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dailyEligibleSessions(User $athlete, $assignment, int $daysBack): array
    {
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
                'exercises' => $this->exercisesFor($trainingDay),
            ];
        }

        return $eligible;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function exercisesFor(ProgramTrainingDay $trainingDay): array
    {
        return $trainingDay->exercises()
            ->get()
            ->map(fn (ProgramDayExercise $exercise) => SessionFeedbackPresenter::seriesOption($exercise))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function weeklyEligibleSessions(User $athlete, $assignment, int $daysBack): array
    {
        $eligible = [];
        $today = now()->startOfDay();
        $seenWeeks = [];

        for ($i = 0; $i <= $daysBack; $i++) {
            $date = $today->copy()->subDays($i);
            [$weekStart] = FeedbackFrequencySupport::weekBounds($date);
            $weekKey = $weekStart->toDateString();

            if (isset($seenWeeks[$weekKey])) {
                continue;
            }

            $seenWeeks[$weekKey] = true;

            if (FeedbackFrequencySupport::hasFeedbackForWeek($athlete, $weekStart)) {
                continue;
            }

            [$weekStartBound, $weekEndBound] = FeedbackFrequencySupport::weekBounds($date);

            if (! ProgramSchedule::hasAnySessionBetween($assignment, $weekStartBound, $weekEndBound)) {
                continue;
            }

            $latestSessionDate = null;
            $latestTrainingDay = null;

            for ($d = $weekEndBound->copy(); $d->gte($weekStartBound); $d->subDay()) {
                $trainingDay = ProgramSchedule::resolveTrainingDayForDate($assignment, $d);
                if ($trainingDay !== null) {
                    $latestSessionDate = $d->copy();
                    $latestTrainingDay = $trainingDay;
                    break;
                }
            }

            if ($latestSessionDate === null || $latestTrainingDay === null) {
                continue;
            }

            $eligible[] = [
                'session_date' => $latestSessionDate->toDateString(),
                'program_training_day_id' => $latestTrainingDay->id,
                'session_label' => sprintf(
                    'Semaine du %s au %s',
                    $weekStartBound->locale('fr')->isoFormat('D MMM'),
                    $weekEndBound->locale('fr')->isoFormat('D MMM YYYY'),
                ),
                'exercises' => $this->exercisesFor($latestTrainingDay),
            ];
        }

        return $eligible;
    }
}
