<?php

namespace App\Support;

use App\Models\TrainingSession;

class TrainingSessionSupport
{
    /**
     * @return array<string, mixed>
     */
    public static function toPayload(TrainingSession $session): array
    {
        return [
            'id' => $session->id,
            'session_date' => $session->session_date->toDateString(),
            'session_label' => $session->session_label,
            'main_lift' => $session->main_lift ?? 'squat',
            'items' => $session->items ?? [],
            'squat' => $session->squat,
            'bench' => $session->bench,
            'deadlift' => $session->deadlift,
            'notes' => $session->notes,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{squat: int, bench: int, deadlift: int}
     */
    public static function computeLiftTotals(array $items, ?string $mainLift = 'squat'): array
    {
        $totals = ['squat' => 0, 'bench' => 0, 'deadlift' => 0];

        foreach ($items as $item) {
            if (empty(trim((string) ($item['exercise_name'] ?? '')))) {
                continue;
            }

            $lift = $item['lift'] ?? $mainLift ?? 'squat';
            if (! in_array($lift, ['squat', 'bench', 'deadlift'], true)) {
                continue;
            }

            $load = isset($item['load']) ? (float) $item['load'] : 0;
            if ($load > 0) {
                $totals[$lift] = max($totals[$lift], (int) round($load));
            }
        }

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    public static function applyValidated(TrainingSession $session, array $validated): void
    {
        $items = $validated['items'] ?? [];
        $mainLift = $validated['main_lift'] ?? 'squat';
        $totals = self::computeLiftTotals($items, $mainLift);

        $session->fill([
            'session_date' => $validated['session_date'],
            'session_label' => $validated['session_label'] ?? null,
            'main_lift' => $mainLift,
            'items' => $items,
            'notes' => $validated['notes'] ?? null,
            'squat' => $totals['squat'],
            'bench' => $totals['bench'],
            'deadlift' => $totals['deadlift'],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public static function hasExerciseContent(array $items): bool
    {
        foreach ($items as $item) {
            if (! empty(trim((string) ($item['exercise_name'] ?? '')))) {
                return true;
            }
        }

        return false;
    }
}
