<?php

namespace App\Services;

use App\Models\AthleteProfile;
use App\Models\AthleteProgramAssignment;
use App\Models\ProgramDayExercise;
use App\Models\ProgramTrainingDay;
use App\Models\SessionFeedback;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\FeedbackFrequencySupport;
use App\Support\ProgramSchedule;
use App\Support\SetSchemeSupport;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CoachTodaySessionsService
{
    /**
     * @return list<array{
     *     athlete_id: int,
     *     athlete: array{id: int, name: string},
     *     session_date: string,
     *     session_label: string|null,
     *     program_name: string|null,
     *     progress_status: 'not_started'|'in_progress'|'done',
     *     has_feedback: bool,
     *     session_feedback_id: int|null,
     *     feedback_frequency: string,
     * }>
     */
    public function forCoach(User $coach): array
    {
        $today = now()->copy()->startOfDay();
        $sessionDate = $today->toDateString();
        [$weekStart, $weekEnd] = FeedbackFrequencySupport::weekBounds($today);

        $athletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->with([
                'profile',
                'programAssignments' => fn ($query) => $query
                    ->where('status', 'active')
                    ->whereDate('date_start', '<=', $today->copy()->addDays(6)->toDateString())
                    ->where(function ($query) use ($sessionDate): void {
                        $query->whereNull('date_end')
                            ->orWhereDate('date_end', '>=', $sessionDate);
                    })
                    ->with('template.weeks.trainingDays.exercises')
                    ->latest('date_start'),
            ])
            ->orderBy('users.name')
            ->get();

        if ($athletes->isEmpty()) {
            return [];
        }

        $athleteIds = $athletes->pluck('id');

        $sessionsByAthlete = TrainingSession::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('session_date', $sessionDate)
            ->orderBy('id')
            ->get()
            ->groupBy('athlete_id');

        $feedbacks = SessionFeedback::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('session_date', '>=', $weekStart->toDateString())
            ->whereDate('session_date', '<=', $weekEnd->toDateString())
            ->orderByDesc('id')
            ->get();

        $dailyFeedbackByAthlete = $feedbacks
            ->filter(fn (SessionFeedback $f) => $f->session_date?->toDateString() === $sessionDate)
            ->groupBy('athlete_id');

        $weeklyFeedbackByAthlete = $feedbacks->groupBy('athlete_id');

        $rows = [];

        foreach ($athletes as $athlete) {
            $assignment = $this->resolveAssignment($athlete, $today);
            if ($assignment === null) {
                continue;
            }

            $trainingDay = ProgramSchedule::resolveTrainingDayForDate($assignment, $today);
            if ($trainingDay === null) {
                continue;
            }

            $loggedItems = $this->mergeLoggedItems($sessionsByAthlete->get($athlete->id) ?? collect());
            $progressStatus = $this->resolveProgressStatus($trainingDay, $loggedItems);

            $frequency = FeedbackFrequencySupport::frequencyFor($athlete);
            $feedback = $frequency === AthleteProfile::FREQUENCY_DAILY
                ? ($dailyFeedbackByAthlete->get($athlete->id)?->first())
                : ($weeklyFeedbackByAthlete->get($athlete->id)?->first());

            $sessionLabel = $trainingDay->session_label
                ?: ($trainingDay->main_lift ? ucfirst((string) $trainingDay->main_lift) : null);

            $rows[] = [
                'athlete_id' => (int) $athlete->id,
                'athlete' => [
                    'id' => (int) $athlete->id,
                    'name' => (string) $athlete->name,
                ],
                'session_date' => $sessionDate,
                'session_label' => $sessionLabel,
                'program_name' => $assignment->template?->name,
                'progress_status' => $progressStatus,
                'has_feedback' => $feedback !== null,
                'session_feedback_id' => $feedback?->id,
                'feedback_frequency' => $frequency,
            ];
        }

        usort($rows, function (array $a, array $b): int {
            $rank = ['in_progress' => 0, 'not_started' => 1, 'done' => 2];
            $rankDiff = ($rank[$a['progress_status']] ?? 9) <=> ($rank[$b['progress_status']] ?? 9);
            if ($rankDiff !== 0) {
                return $rankDiff;
            }

            return strcasecmp($a['athlete']['name'], $b['athlete']['name']);
        });

        return $rows;
    }

    private function resolveAssignment(User $athlete, Carbon $today): ?AthleteProgramAssignment
    {
        return $athlete->programAssignments->first(
            fn (AthleteProgramAssignment $assignment): bool => ProgramSchedule::isDateOnSchedule($assignment, $today),
        );
    }

    /**
     * @param  Collection<int, TrainingSession>  $sessions
     * @return list<array<string, mixed>>
     */
    private function mergeLoggedItems(Collection $sessions): array
    {
        $items = [];

        foreach ($sessions as $session) {
            foreach ($session->items ?? [] as $item) {
                if (! is_array($item)) {
                    continue;
                }
                if (trim((string) ($item['exercise_name'] ?? '')) === '') {
                    continue;
                }
                if (($item['section'] ?? null) === ProgramDayExercise::SECTION_WARMUP) {
                    continue;
                }

                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @param  list<array<string, mixed>>  $loggedItems
     * @return 'not_started'|'in_progress'|'done'
     */
    private function resolveProgressStatus(ProgramTrainingDay $trainingDay, array $loggedItems): string
    {
        $planned = $this->plannedWorkLines($trainingDay);
        $loggedByKey = $this->groupLoggedByKey($loggedItems);
        $totalLoggedSets = 0;

        foreach ($loggedByKey as $group) {
            $totalLoggedSets += $this->countLoggedSets($group);
        }

        if ($totalLoggedSets === 0) {
            return 'not_started';
        }

        if ($planned === []) {
            return 'in_progress';
        }

        foreach ($planned as $line) {
            $key = $this->itemKey($line['section'] ?? null, $line['exercise_name'] ?? '');
            $plannedSets = $this->plannedSetsForLine($line);
            $loggedSets = $this->countLoggedSets($loggedByKey[$key] ?? []);

            if ($loggedSets < $plannedSets) {
                return 'in_progress';
            }
        }

        return 'done';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function plannedWorkLines(ProgramTrainingDay $trainingDay): array
    {
        $trainingDay->loadMissing('exercises');
        $lines = [];

        foreach ($trainingDay->exercises as $exercise) {
            if ($exercise->section === ProgramDayExercise::SECTION_WARMUP) {
                continue;
            }
            if (trim((string) ($exercise->exercise_name ?? '')) === '') {
                continue;
            }

            $lines[] = [
                'section' => $exercise->section,
                'exercise_variant_id' => $exercise->exercise_variant_id,
                'exercise_name' => $exercise->exercise_name,
                'set_scheme' => SetSchemeSupport::resolveScheme($exercise->set_scheme),
                'scheme_config' => is_array($exercise->scheme_config) ? $exercise->scheme_config : null,
                'sets' => $exercise->sets,
            ];
        }

        return $lines;
    }

    /**
     * @param  list<array<string, mixed>>  $loggedItems
     * @return array<string, list<array<string, mixed>>>
     */
    private function groupLoggedByKey(array $loggedItems): array
    {
        $map = [];

        foreach ($loggedItems as $item) {
            $key = $this->itemKey($item['section'] ?? null, $item['exercise_name'] ?? '');
            $map[$key][] = $item;
        }

        return $map;
    }

    private function itemKey(mixed $section, mixed $exerciseName): string
    {
        return (string) ($section ?? '').'-'.trim((string) $exerciseName);
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function plannedSetsForLine(array $line): int
    {
        $scheme = SetSchemeSupport::resolveScheme($line['set_scheme'] ?? null);

        if ($scheme === ProgramDayExercise::SCHEME_RAMP) {
            $steps = $line['scheme_config']['steps'] ?? [];
            $stepCount = is_array($steps) ? count($steps) : 0;

            return max(1, $stepCount ?: (int) ($line['sets'] ?? 1) ?: 1);
        }

        if ($scheme === ProgramDayExercise::SCHEME_CLUSTER) {
            return 1;
        }

        return max(1, (int) ($line['sets'] ?? 1) ?: 1);
    }

    /**
     * @param  list<array<string, mixed>>  $loggedGroup
     */
    private function countLoggedSets(array $loggedGroup): int
    {
        $count = 0;

        foreach ($loggedGroup as $item) {
            $sets = max(1, (int) ($item['sets'] ?? 1) ?: 1);
            $count += $sets;
        }

        return $count;
    }
}
