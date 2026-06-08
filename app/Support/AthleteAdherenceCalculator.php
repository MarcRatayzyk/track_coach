<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\TrainingSession;
use Carbon\CarbonInterface;

class AthleteAdherenceCalculator
{
    /**
     * Adhérence sur une période : séances réalisées + concordance séries / reps / charges.
     *
     * @return array{
     *     planned_sessions: int,
     *     completed_sessions: int,
     *     session_coverage: int|null,
     *     matched_checks: int,
     *     total_checks: int,
     *     percentage: int|null,
     *     planned_lines: int,
     *     exact_lines: int,
     * }
     */
    public function between(
        int $athleteId,
        AthleteProgramAssignment $assignment,
        CarbonInterface $start,
        CarbonInterface $end,
    ): array {
        $assignment->loadMissing([
            'template.weeks.trainingDays.exercises',
            'athlete.latestPr',
        ]);

        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();

        if ($end->lt($start)) {
            return $this->emptyResult();
        }

        $oneRm = [
            'squat' => (int) ($assignment->athlete?->latestPr?->squat ?? 0),
            'bench' => (int) ($assignment->athlete?->latestPr?->bench ?? 0),
            'deadlift' => (int) ($assignment->athlete?->latestPr?->deadlift ?? 0),
        ];

        $actualByDate = $this->mergeSessionsByDate(
            TrainingSession::query()
                ->where('athlete_id', $athleteId)
                ->whereDate('session_date', '>=', $start->toDateString())
                ->whereDate('session_date', '<=', $end->toDateString())
                ->orderBy('session_date')
                ->orderBy('id')
                ->get(),
        );

        $plannedSessions = 0;
        $completedSessions = 0;
        $matchedChecks = 0;
        $totalChecks = 0;
        $exactLines = 0;
        $plannedLines = 0;

        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if (! $this->isDateWithinAssignment($assignment, $cursor)) {
                $cursor = $cursor->copy()->addDay();

                continue;
            }

            $day = ProgramSchedule::resolveTrainingDayForDate($assignment, $cursor);
            $plannedItems = $this->plannedItemsForDay($day);

            if ($plannedItems === []) {
                $cursor = $cursor->copy()->addDay();

                continue;
            }

            $plannedSessions++;
            $dateKey = $cursor->toDateString();
            $actualItems = $actualByDate[$dateKey]['items'] ?? [];

            if ($actualItems !== []) {
                $completedSessions++;
            }

            $mainLift = $day?->main_lift ?? 'squat';
            $usedIndices = [];

            foreach ($plannedItems as $plannedLine) {
                $plannedLines++;
                $score = $this->scorePlannedLine($plannedLine, $actualItems, $usedIndices, $oneRm, $mainLift);
                $matchedChecks += $score['matched_checks'];
                $totalChecks += $score['total_checks'];

                if ($score['exact']) {
                    $exactLines++;
                }
            }

            $cursor = $cursor->copy()->addDay();
        }

        return [
            'planned_sessions' => $plannedSessions,
            'completed_sessions' => $completedSessions,
            'session_coverage' => $plannedSessions > 0
                ? (int) round(($completedSessions / $plannedSessions) * 100)
                : null,
            'matched_checks' => $matchedChecks,
            'total_checks' => $totalChecks,
            'percentage' => $totalChecks > 0
                ? (int) round(($matchedChecks / $totalChecks) * 100)
                : null,
            'planned_lines' => $plannedLines,
            'exact_lines' => $exactLines,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, TrainingSession>  $sessions
     * @return array<string, array{items: list<array<string, mixed>>}>
     */
    private function mergeSessionsByDate($sessions): array
    {
        $map = [];

        foreach ($sessions as $session) {
            $dateKey = $session->session_date->toDateString();
            $mainLift = $session->main_lift ?? 'squat';

            if (! isset($map[$dateKey])) {
                $map[$dateKey] = ['items' => []];
            }

            foreach ($session->items ?? [] as $item) {
                if (trim((string) ($item['exercise_name'] ?? '')) === '') {
                    continue;
                }

                $map[$dateKey]['items'][] = array_merge($item, [
                    'lift' => in_array($item['lift'] ?? null, ['squat', 'bench', 'deadlift'], true)
                        ? $item['lift']
                        : $mainLift,
                ]);
            }
        }

        return $map;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function plannedItemsForDay(?ProgramTrainingDay $day): array
    {
        if ($day === null) {
            return [];
        }

        if (! $day->relationLoaded('exercises')) {
            $day->load('exercises');
        }

        $items = [];

        foreach ($day->exercises as $exercise) {
            if (trim((string) ($exercise->exercise_name ?? '')) === '') {
                continue;
            }

            $items[] = TrainingLoadSupport::exerciseLineToArray($exercise);
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $plannedLine
     * @param  list<array<string, mixed>>  $actualItems
     * @param  list<int>  $usedIndices
     * @param  array{squat?: int, bench?: int, deadlift?: int}  $oneRm
     * @return array{matched_checks: int, total_checks: int, exact: bool}
     */
    private function scorePlannedLine(
        array $plannedLine,
        array $actualItems,
        array &$usedIndices,
        array $oneRm,
        string $fallbackLift,
    ): array {
        $bestIndex = -1;
        $bestMatchedChecks = 0;
        $bestTotalChecks = 1;
        $plannedName = strtolower(trim((string) ($plannedLine['exercise_name'] ?? '')));

        foreach ($actualItems as $index => $actualLine) {
            if (in_array($index, $usedIndices, true)) {
                continue;
            }

            $sameVariant = ! empty($plannedLine['exercise_variant_id'])
                && ! empty($actualLine['exercise_variant_id'])
                && (int) $plannedLine['exercise_variant_id'] === (int) $actualLine['exercise_variant_id'];
            $sameName = strtolower(trim((string) ($actualLine['exercise_name'] ?? ''))) === $plannedName;

            if (! $sameVariant && ! $sameName) {
                continue;
            }

            $matchedChecks = 1;
            $totalChecks = 1;

            if (TrainingLoadSupport::hasNumericValue($plannedLine['sets'] ?? null)) {
                $totalChecks++;
                if (TrainingLoadSupport::valuesMatch($plannedLine['sets'], $actualLine['sets'] ?? null)) {
                    $matchedChecks++;
                }
            }

            if (TrainingLoadSupport::hasNumericValue($plannedLine['reps'] ?? null)) {
                $totalChecks++;
                if (TrainingLoadSupport::valuesMatch($plannedLine['reps'], $actualLine['reps'] ?? null)) {
                    $matchedChecks++;
                }
            }

            if (TrainingLoadSupport::hasExplicitLoadTarget($plannedLine)) {
                $totalChecks++;
                if (TrainingLoadSupport::loadsMatch($plannedLine, $actualLine, $oneRm, $fallbackLift)) {
                    $matchedChecks++;
                }
            }

            $bestRatio = $bestTotalChecks > 0 ? $bestMatchedChecks / $bestTotalChecks : 0;
            $candidateRatio = $totalChecks > 0 ? $matchedChecks / $totalChecks : 0;

            if (
                $bestIndex === -1
                || $candidateRatio > $bestRatio
                || ($candidateRatio === $bestRatio && $matchedChecks > $bestMatchedChecks)
            ) {
                $bestIndex = $index;
                $bestMatchedChecks = $matchedChecks;
                $bestTotalChecks = $totalChecks;
            }
        }

        if ($bestIndex >= 0) {
            $usedIndices[] = $bestIndex;
        } else {
            $bestTotalChecks = 1;
            if (TrainingLoadSupport::hasNumericValue($plannedLine['sets'] ?? null)) {
                $bestTotalChecks++;
            }
            if (TrainingLoadSupport::hasNumericValue($plannedLine['reps'] ?? null)) {
                $bestTotalChecks++;
            }
            if (TrainingLoadSupport::hasExplicitLoadTarget($plannedLine)) {
                $bestTotalChecks++;
            }
            $bestMatchedChecks = 0;
        }

        return [
            'matched_checks' => $bestMatchedChecks,
            'total_checks' => $bestTotalChecks,
            'exact' => $bestMatchedChecks === $bestTotalChecks && $bestTotalChecks > 0,
        ];
    }

    private function isDateWithinAssignment(
        AthleteProgramAssignment $assignment,
        CarbonInterface $date,
    ): bool {
        if ($date->lt($assignment->date_start->copy()->startOfDay())) {
            return false;
        }

        if ($assignment->date_end !== null && $date->gt($assignment->date_end->copy()->startOfDay())) {
            return false;
        }

        return true;
    }

    /**
     * @return array{
     *     planned_sessions: int,
     *     completed_sessions: int,
     *     session_coverage: int|null,
     *     matched_checks: int,
     *     total_checks: int,
     *     percentage: int|null,
     *     planned_lines: int,
     *     exact_lines: int,
     * }
     */
    private function emptyResult(): array
    {
        return [
            'planned_sessions' => 0,
            'completed_sessions' => 0,
            'session_coverage' => null,
            'matched_checks' => 0,
            'total_checks' => 0,
            'percentage' => null,
            'planned_lines' => 0,
            'exact_lines' => 0,
        ];
    }
}
