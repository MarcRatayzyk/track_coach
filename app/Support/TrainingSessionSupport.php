<?php

namespace App\Support;

use App\Models\TrainingSession;
use Carbon\CarbonInterface;

class TrainingSessionSupport
{
    /** Un segment ouvert oublié ne doit pas compter plusieurs jours dans le récap. */
    public const MAX_TIMER_SEGMENT_SECONDS = 8 * 60 * 60;

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
            'timer_started_at' => $session->timer_started_at?->toIso8601String(),
            'timer_elapsed_seconds' => (int) $session->timer_elapsed_seconds,
            'finished_at' => $session->finished_at?->toIso8601String(),
        ];
    }

    /**
     * Secondes chronométrées : segments déjà arrêtés + segment encore en cours.
     * Le départ est une date, donc le temps continue même si l'app est fermée.
     */
    public static function recordedSeconds(TrainingSession $session, ?CarbonInterface $now = null): int
    {
        $elapsed = max(0, (int) $session->timer_elapsed_seconds);
        $startedAt = $session->timer_started_at;

        if ($startedAt === null) {
            return $elapsed;
        }

        $now = $now ?? now();
        $open = (int) max(0, $startedAt->diffInSeconds($now, absolute: false));

        return $elapsed + min($open, self::MAX_TIMER_SEGMENT_SECONDS);
    }

    public static function closeOpenTimer(TrainingSession $session, ?CarbonInterface $now = null): void
    {
        if ($session->timer_started_at === null) {
            return;
        }

        $now = $now ?? now();
        $open = (int) max(0, $session->timer_started_at->diffInSeconds($now, absolute: false));
        $session->timer_elapsed_seconds = max(0, (int) $session->timer_elapsed_seconds) + min($open, self::MAX_TIMER_SEGMENT_SECONDS);
        $session->timer_started_at = null;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array{squat: int, bench: int, deadlift: int}
     */
    public static function computeLiftTotals(array $items, ?string $mainLift = 'squat'): array
    {
        $totals = ['squat' => 0, 'bench' => 0, 'deadlift' => 0];

        foreach ($items as $item) {
            if (($item['section'] ?? null) === 'warmup') {
                continue;
            }

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

        if (array_key_exists('timer_elapsed_seconds', $validated) && $validated['timer_elapsed_seconds'] !== null) {
            $session->timer_elapsed_seconds = max(0, (int) $validated['timer_elapsed_seconds']);
            $session->timer_started_at = null;
        }
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
