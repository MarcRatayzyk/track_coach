<?php

namespace App\Support;

use App\Models\ProgramDayExercise;

class TrainingLoadSupport
{
    private const LIFTS = ['squat', 'bench', 'deadlift'];

    /**
     * @param  array<string, mixed>  $line
     * @param  array{squat?: int, bench?: int, deadlift?: int}  $oneRm
     */
    public static function resolveLoadKg(array $line, array $oneRm = [], string $fallbackLift = 'squat'): ?float
    {
        $mode = self::inferLoadMode($line);
        $lift = self::resolveLift($line, $fallbackLift);

        if ($mode === 'rpe') {
            return null;
        }

        if ($mode === 'kg') {
            $load = (float) ($line['load'] ?? 0);

            return $load > 0 ? $load : null;
        }

        if ($mode === 'percent') {
            $pct = (float) ($line['load_percent'] ?? 0);
            $rm = (float) ($oneRm[$lift] ?? 0);

            if ($pct <= 0 || $rm <= 0) {
                return null;
            }

            return ($pct / 100) * $rm;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public static function hasExplicitLoadTarget(array $line): bool
    {
        return self::hasNumericValue($line['load'] ?? null)
            || self::hasNumericValue($line['load_percent'] ?? null)
            || self::hasNumericValue($line['rpe'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $plannedLine
     * @param  array<string, mixed>  $actualLine
     * @param  array{squat?: int, bench?: int, deadlift?: int}  $oneRm
     */
    public static function loadsMatch(
        array $plannedLine,
        array $actualLine,
        array $oneRm,
        string $fallbackLift,
    ): bool {
        $plannedKg = self::resolveLoadKg($plannedLine, $oneRm, $fallbackLift);
        $actualKg = self::resolveLoadKg($actualLine, $oneRm, $fallbackLift);

        if ($plannedKg !== null && $actualKg !== null) {
            return abs($plannedKg - $actualKg) < 0.25;
        }

        if (self::hasNumericValue($plannedLine['rpe'] ?? null) || self::hasNumericValue($actualLine['rpe'] ?? null)) {
            return self::valuesMatch($plannedLine['rpe'] ?? null, $actualLine['rpe'] ?? null);
        }

        if (
            self::hasNumericValue($plannedLine['load_percent'] ?? null)
            || self::hasNumericValue($actualLine['load_percent'] ?? null)
        ) {
            return self::valuesMatch($plannedLine['load_percent'] ?? null, $actualLine['load_percent'] ?? null);
        }

        return self::valuesMatch($plannedLine['load'] ?? null, $actualLine['load'] ?? null);
    }

    public static function valuesMatch(mixed $a, mixed $b): bool
    {
        if (! self::hasNumericValue($a) || ! self::hasNumericValue($b)) {
            return false;
        }

        return abs((float) $a - (float) $b) < 0.05;
    }

    public static function hasNumericValue(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return is_numeric($value);
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public static function exerciseLineToArray(ProgramDayExercise $line): array
    {
        return [
            'exercise_variant_id' => $line->exercise_variant_id,
            'exercise_name' => $line->exercise_name,
            'lift' => $line->lift,
            'sets' => $line->sets,
            'reps' => $line->reps,
            'load' => $line->load,
            'load_percent' => $line->load_percent,
            'rpe' => $line->rpe,
        ];
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private static function inferLoadMode(array $line): ?string
    {
        if (self::hasNumericValue($line['rpe'] ?? null)) {
            return 'rpe';
        }

        if (self::hasNumericValue($line['load_percent'] ?? null)) {
            return 'percent';
        }

        if (self::hasNumericValue($line['load'] ?? null)) {
            return 'kg';
        }

        $mode = $line['load_mode'] ?? null;

        return is_string($mode) && $mode !== '' ? $mode : null;
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private static function resolveLift(array $line, string $fallbackLift): string
    {
        $lift = $line['lift'] ?? $fallbackLift;

        return in_array($lift, self::LIFTS, true) ? $lift : 'squat';
    }
}
