<?php

namespace App\Support;

use App\Models\ProgramDayExercise;

class SetSchemeSupport
{
    /**
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    public static function normalizeLine(array $line): array
    {
        $scheme = self::resolveScheme($line['set_scheme'] ?? null);

        if ($scheme === ProgramDayExercise::SCHEME_RAMP) {
            $steps = self::normalizeRampSteps($line['scheme_config']['steps'] ?? []);
            $last = $steps !== [] ? $steps[array_key_last($steps)] : null;

            return array_merge($line, [
                'set_scheme' => $scheme,
                'scheme_config' => ['steps' => $steps],
                'sets' => max(1, count($steps)),
                'reps' => $last['reps'] ?? ($line['reps'] ?? 1),
                'load' => $last['load'] ?? null,
                'load_percent' => $last['load_percent'] ?? null,
                'rpe' => $last['rpe'] ?? null,
            ]);
        }

        if ($scheme === ProgramDayExercise::SCHEME_CLUSTER) {
            $reps = self::nullableInt($line['scheme_config']['reps'] ?? $line['reps'] ?? null) ?? 1;
            $minutes = self::nullableInt($line['scheme_config']['duration_minutes'] ?? null) ?? 1;

            return array_merge($line, [
                'set_scheme' => $scheme,
                'scheme_config' => [
                    'reps' => max(1, min(200, $reps)),
                    'duration_minutes' => max(1, min(60, $minutes)),
                ],
                'sets' => 1,
                'reps' => max(1, min(200, $reps)),
                'load' => $line['load'] ?? null,
                'load_percent' => $line['load_percent'] ?? null,
                'rpe' => $line['rpe'] ?? null,
            ]);
        }

        return array_merge($line, [
            'set_scheme' => ProgramDayExercise::SCHEME_STANDARD,
            'scheme_config' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $line
     */
    public static function formatPrescription(array $line): string
    {
        $scheme = self::resolveScheme($line['set_scheme'] ?? null);

        if ($scheme === ProgramDayExercise::SCHEME_RAMP) {
            $steps = self::normalizeRampSteps($line['scheme_config']['steps'] ?? []);
            if ($steps === []) {
                return '';
            }

            return implode(' → ', array_map(
                static fn (array $step): string => self::formatStep($step),
                $steps,
            ));
        }

        if ($scheme === ProgramDayExercise::SCHEME_CLUSTER) {
            $reps = self::nullableInt($line['scheme_config']['reps'] ?? $line['reps'] ?? null);
            $minutes = self::nullableInt($line['scheme_config']['duration_minutes'] ?? null);
            if ($reps === null || $minutes === null) {
                return '';
            }

            return "{$reps} reps / {$minutes} min";
        }

        return '';
    }

    public static function resolveScheme(mixed $scheme): string
    {
        $value = is_string($scheme) ? $scheme : ProgramDayExercise::SCHEME_STANDARD;

        return in_array($value, ProgramDayExercise::SCHEMES, true)
            ? $value
            : ProgramDayExercise::SCHEME_STANDARD;
    }

    /**
     * @param  mixed  $steps
     * @return list<array{reps: int, load: float|null, load_percent: float|null, rpe: float|null}>
     */
    public static function normalizeRampSteps(mixed $steps): array
    {
        if (! is_array($steps)) {
            return [];
        }

        $normalized = [];

        foreach (array_slice(array_values($steps), 0, 8) as $step) {
            if (! is_array($step)) {
                continue;
            }

            $reps = self::nullableInt($step['reps'] ?? null);
            if ($reps === null || $reps < 1) {
                continue;
            }

            $load = self::nullableFloat($step['load'] ?? null);
            $loadPercent = self::nullableFloat($step['load_percent'] ?? null);
            $rpe = self::nullableFloat($step['rpe'] ?? null);

            if ($load === null && $loadPercent === null && $rpe === null) {
                continue;
            }

            $normalized[] = [
                'reps' => max(1, min(20, $reps)),
                'load' => $load,
                'load_percent' => $loadPercent,
                'rpe' => $rpe,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array{reps: int, load: float|null, load_percent: float|null, rpe: float|null}  $step
     */
    private static function formatStep(array $step): string
    {
        $loadPart = '—';
        if ($step['load'] !== null) {
            $loadPart = self::formatNumber((float) $step['load']).' kg';
        } elseif ($step['load_percent'] !== null) {
            $loadPart = self::formatNumber((float) $step['load_percent']).'%';
        } elseif ($step['rpe'] !== null) {
            $loadPart = 'RPE '.self::formatNumber((float) $step['rpe']);
        }

        return $step['reps'].'@'.$loadPart;
    }

    private static function formatNumber(float $value): string
    {
        if (floor($value) === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
