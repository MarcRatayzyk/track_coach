<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\TrainingSession;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AthleteAdherenceCalculator
{
    /**
     * Adhérence sur une période : séances réalisées + concordance séries / reps / charges.
     *
     * @return array{
     *     planned_sessions: int,
     *     completed_sessions: int,
     *     missed_sessions: int,
     *     session_coverage: int|null,
     *     matched_checks: int,
     *     total_checks: int,
     *     percentage: int|null,
     *     planned_lines: int,
     *     exact_lines: int,
     *     missed_exercises: int,
     *     mismatched_sets: int,
     * }
     */
    public function between(
        int $athleteId,
        AthleteProgramAssignment $assignment,
        CarbonInterface $start,
        CarbonInterface $end,
        ?Collection $preloadedSessions = null,
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

        $sessions = $preloadedSessions ?? TrainingSession::query()
            ->where('athlete_id', $athleteId)
            ->whereDate('session_date', '>=', $start->toDateString())
            ->whereDate('session_date', '<=', $end->toDateString())
            ->orderBy('session_date')
            ->orderBy('id')
            ->get();

        if ($preloadedSessions !== null) {
            $startString = $start->toDateString();
            $endString = $end->toDateString();
            $sessions = $sessions
                ->filter(fn (TrainingSession $session) => $session->session_date !== null
                    && $session->session_date->toDateString() >= $startString
                    && $session->session_date->toDateString() <= $endString)
                ->values();
        }

        $actualByDate = $this->mergeSessionsByDate($sessions);

        $plannedSessions = 0;
        $completedSessions = 0;
        $matchedChecks = 0;
        $totalChecks = 0;
        $exactLines = 0;
        $plannedLines = 0;
        $missedExercises = 0;
        $mismatchedSets = 0;

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

            $dateKey = $cursor->toDateString();
            $actualItems = $actualByDate[$dateKey]['items'] ?? [];
            $isToday = $dateKey === $end->toDateString();

            if ($actualItems === [] && $isToday) {
                $cursor = $cursor->copy()->addDay();

                continue;
            }

            $plannedSessions++;

            if ($actualItems !== []) {
                $completedSessions++;
            }

            $mainLift = $day?->main_lift ?? 'squat';
            $usedIndices = [];
            $sessionWasLogged = $actualItems !== [];

            foreach ($plannedItems as $plannedLine) {
                $plannedLines++;
                $score = $this->scorePlannedLine($plannedLine, $actualItems, $usedIndices, $oneRm, $mainLift);
                $matchedChecks += $score['matched_checks'];
                $totalChecks += $score['total_checks'];

                if ($score['exact']) {
                    $exactLines++;
                }

                // Détail exo/séries uniquement si la séance a été ouverte
                // (sinon le résumé « X séances non enregistrées » suffit).
                if ($sessionWasLogged) {
                    if (! $score['found']) {
                        $missedExercises++;
                    }

                    if ($score['sets_mismatch']) {
                        $mismatchedSets++;
                    }
                }
            }

            $cursor = $cursor->copy()->addDay();
        }

        return [
            'planned_sessions' => $plannedSessions,
            'completed_sessions' => $completedSessions,
            'missed_sessions' => max(0, $plannedSessions - $completedSessions),
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
            'missed_exercises' => $missedExercises,
            'mismatched_sets' => $mismatchedSets,
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
                if (($item['section'] ?? null) === 'warmup') {
                    continue;
                }

                $map[$dateKey]['items'][] = array_merge($item, [
                    'section' => $item['section'] ?? null,
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
            if ($exercise->section === 'warmup') {
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
     * @return array{
     *     matched_checks: int,
     *     total_checks: int,
     *     exact: bool,
     *     found: bool,
     *     sets_mismatch: bool,
     * }
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
        $bestSetsMatch = false;
        $hasSetsTarget = TrainingLoadSupport::hasNumericValue($plannedLine['sets'] ?? null);
        $plannedName = strtolower(trim((string) ($plannedLine['exercise_name'] ?? '')));
        $plannedLift = in_array($plannedLine['lift'] ?? null, ['squat', 'bench', 'deadlift'], true)
            ? $plannedLine['lift']
            : $fallbackLift;

        foreach ($actualItems as $index => $actualLine) {
            if (in_array($index, $usedIndices, true)) {
                continue;
            }

            if (! self::sectionsCompatible($plannedLine, $actualLine)) {
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
            $setsMatch = ! $hasSetsTarget;

            if ($hasSetsTarget) {
                $totalChecks++;
                if (TrainingLoadSupport::valuesMatch($plannedLine['sets'], $actualLine['sets'] ?? null)) {
                    $matchedChecks++;
                    $setsMatch = true;
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
                if (TrainingLoadSupport::loadsMatch($plannedLine, $actualLine, $oneRm, $plannedLift)) {
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
                $bestSetsMatch = $setsMatch;
            }
        }

        if ($bestIndex >= 0) {
            $usedIndices[] = $bestIndex;
        } else {
            $bestTotalChecks = 1;
            if ($hasSetsTarget) {
                $bestTotalChecks++;
            }
            if (TrainingLoadSupport::hasNumericValue($plannedLine['reps'] ?? null)) {
                $bestTotalChecks++;
            }
            if (TrainingLoadSupport::hasExplicitLoadTarget($plannedLine)) {
                $bestTotalChecks++;
            }
            $bestMatchedChecks = 0;
            $bestSetsMatch = false;
        }

        return [
            'matched_checks' => $bestMatchedChecks,
            'total_checks' => $bestTotalChecks,
            'exact' => $bestMatchedChecks === $bestTotalChecks && $bestTotalChecks > 0,
            'found' => $bestIndex >= 0,
            'sets_mismatch' => $hasSetsTarget && $bestIndex >= 0 && ! $bestSetsMatch,
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
     * @param  array<string, mixed>  $plannedLine
     * @param  array<string, mixed>  $actualLine
     */
    private function sectionsCompatible(array $plannedLine, array $actualLine): bool
    {
        $plannedSection = trim((string) ($plannedLine['section'] ?? ''));
        $actualSection = trim((string) ($actualLine['section'] ?? ''));

        if ($plannedSection === '' || $actualSection === '') {
            return true;
        }

        return $plannedSection === $actualSection;
    }

    private function emptyResult(): array
    {
        return [
            'planned_sessions' => 0,
            'completed_sessions' => 0,
            'missed_sessions' => 0,
            'session_coverage' => null,
            'matched_checks' => 0,
            'total_checks' => 0,
            'percentage' => null,
            'planned_lines' => 0,
            'exact_lines' => 0,
            'missed_exercises' => 0,
            'mismatched_sets' => 0,
        ];
    }
}
