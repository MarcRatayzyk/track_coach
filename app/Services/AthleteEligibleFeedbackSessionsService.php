<?php

namespace App\Services;

use App\Models\ProgramDayExercise;
use App\Models\ProgramTrainingDay;
use App\Models\SessionFeedback;
use App\Models\User;
use App\Support\ActiveProgramAssignmentSupport;
use App\Support\FeedbackFrequencySupport;
use App\Support\ProgramSchedule;
use App\Support\SessionFeedbackPresenter;

class AthleteEligibleFeedbackSessionsService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forAthlete(User $athlete, int $daysBack = 14): array
    {
        if (FeedbackFrequencySupport::isWeekly($athlete)) {
            $assignment = ActiveProgramAssignmentSupport::forAthleteOnDate(
                $athlete,
                now(),
                ['template.weeks.trainingDays'],
            );

            if ($assignment === null) {
                return [];
            }

            return $this->weeklyEligibleSessions($athlete, $assignment, $daysBack);
        }

        return $this->dailyEligibleSessions($athlete, $daysBack);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function dailyEligibleSessions(User $athlete, int $daysBack): array
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

            $assignment = ActiveProgramAssignmentSupport::forAthleteOnDate(
                $athlete,
                $date,
                ['template.weeks.trainingDays'],
            );

            if ($assignment === null) {
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
                'logged_notes' => SessionFeedbackPresenter::loggedNotesForAthleteBetween(
                    $athlete->id,
                    $dateString,
                    $dateString,
                ),
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
                    $weekStartBound->locale(app()->getLocale())->isoFormat('D MMM'),
                    $weekEndBound->locale(app()->getLocale())->isoFormat('D MMM YYYY'),
                ),
                'exercises' => $this->exercisesFor($latestTrainingDay),
                'logged_notes' => SessionFeedbackPresenter::loggedNotesForAthleteBetween(
                    $athlete->id,
                    $weekStartBound->toDateString(),
                    $weekEndBound->toDateString(),
                ),
            ];
        }

        return $eligible;
    }
}
