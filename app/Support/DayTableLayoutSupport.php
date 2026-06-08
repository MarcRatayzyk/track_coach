<?php

namespace App\Support;

use App\Models\DayTableLayout;
use App\Models\User;

class DayTableLayoutSupport
{
    /**
     * @return list<string>
     */
    public static function allowedColumnIds(): array
    {
        return ['section', 'sets', 'reps', 'load', 'rest', 'muscles'];
    }

    /**
     * @return list<string>
     */
    public static function allowedExerciseModes(): array
    {
        return [
            DayTableLayout::EXERCISE_MODE_NAME,
            DayTableLayout::EXERCISE_MODE_SPLIT_LIFT,
        ];
    }

    /**
     * @return list<string>
     */
    public static function allowedLoadModes(): array
    {
        return [
            DayTableLayout::LOAD_MODE_KG,
            DayTableLayout::LOAD_MODE_PERCENT,
            DayTableLayout::LOAD_MODE_RPE,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizePayload(array $data): array
    {
        $columns = collect($data['columns'] ?? [])
            ->filter(fn ($column) => is_string($column) && in_array($column, self::allowedColumnIds(), true))
            ->values()
            ->all();

        $exerciseMode = in_array($data['exercise_mode'] ?? null, self::allowedExerciseModes(), true)
            ? $data['exercise_mode']
            : DayTableLayout::EXERCISE_MODE_NAME;

        $loadMode = in_array($data['load_mode'] ?? null, self::allowedLoadModes(), true)
            ? $data['load_mode']
            : DayTableLayout::LOAD_MODE_KG;

        return [
            'name' => trim((string) ($data['name'] ?? '')),
            'columns' => $columns,
            'exercise_mode' => $exerciseMode,
            'load_mode' => $loadMode,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ];
    }

    public static function ensureCoachHasDefaultLayout(User $coach): DayTableLayout
    {
        $existing = DayTableLayout::query()
            ->where('coach_id', $coach->id)
            ->where('is_default', true)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $fallback = DayTableLayout::query()
            ->where('coach_id', $coach->id)
            ->first();

        if ($fallback !== null) {
            $fallback->update(['is_default' => true]);

            return $fallback->fresh();
        }

        return DayTableLayout::create([
            'coach_id' => $coach->id,
            'name' => 'Classique',
            'columns' => ['section', 'sets', 'reps', 'load'],
            'exercise_mode' => DayTableLayout::EXERCISE_MODE_NAME,
            'load_mode' => DayTableLayout::LOAD_MODE_KG,
            'is_default' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function resolveSnapshot(?array $tableLayout): array
    {
        if (! is_array($tableLayout) || $tableLayout === []) {
            return DayTableLayout::classicSnapshot();
        }

        return self::normalizePayload([
            'name' => 'snapshot',
            'columns' => $tableLayout['columns'] ?? [],
            'exercise_mode' => $tableLayout['exercise_mode'] ?? DayTableLayout::EXERCISE_MODE_NAME,
            'load_mode' => $tableLayout['load_mode'] ?? DayTableLayout::LOAD_MODE_KG,
            'is_default' => false,
        ]);
    }
}
