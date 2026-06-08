<?php

namespace App\Support;

use App\Models\CoachStatsDashboardItem;
use App\Models\User;

class ChartTemplateSupport
{
    /**
     * @return list<string>
     */
    public static function allowedChartTypes(): array
    {
        return ['bar', 'line', 'doughnut'];
    }

    /**
     * @return list<string>
     */
    public static function allowedMetrics(): array
    {
        return ['volume', 'avgLoad', 'e1rm', 'setsCount', 'totalReps', 'tonnage'];
    }

    /**
     * @return list<string>
     */
    public static function allowedGroupBy(): array
    {
        return ['week', 'day', 'lift', 'section', 'exercise'];
    }

    /**
     * @return list<string>
     */
    public static function allowedSeries(): array
    {
        return ['squat', 'bench', 'deadlift'];
    }

    /**
     * @return list<string>
     */
    public static function allowedMainLiftFilters(): array
    {
        return ['all', 'squat', 'bench', 'deadlift'];
    }

    /**
     * @return list<string>
     */
    public static function allowedRepFormats(): array
    {
        return ['all', 'single', 'double', 'triple', '4', '5', '6plus'];
    }

    /**
     * @return list<string>
     */
    public static function allowedSections(): array
    {
        return ['all', 'topset', 'backoff', 'accessory'];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultConfig(): array
    {
        return [
            'chartType' => 'bar',
            'metric' => 'volume',
            'groupBy' => 'week',
            'series' => ['squat', 'bench', 'deadlift'],
            'stacked' => true,
            'filters' => [
                'mainLift' => 'all',
                'repFormat' => 'all',
                'section' => 'all',
                'weekFrom' => null,
                'weekTo' => null,
                'exerciseName' => null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizePayload(array $data): array
    {
        $defaults = self::defaultConfig();
        $filters = is_array($data['filters'] ?? null) ? $data['filters'] : [];

        $chartType = in_array($data['chartType'] ?? null, self::allowedChartTypes(), true)
            ? $data['chartType']
            : $defaults['chartType'];

        $metric = in_array($data['metric'] ?? null, self::allowedMetrics(), true)
            ? $data['metric']
            : $defaults['metric'];

        $groupBy = in_array($data['groupBy'] ?? null, self::allowedGroupBy(), true)
            ? $data['groupBy']
            : $defaults['groupBy'];

        $series = collect($data['series'] ?? [])
            ->filter(fn ($lift) => is_string($lift) && in_array($lift, self::allowedSeries(), true))
            ->values()
            ->all();

        if ($series === []) {
            $series = $defaults['series'];
        }

        $weekFrom = self::nullablePositiveInt($filters['weekFrom'] ?? null);
        $weekTo = self::nullablePositiveInt($filters['weekTo'] ?? null);

        if ($weekFrom !== null && $weekTo !== null && $weekFrom > $weekTo) {
            [$weekFrom, $weekTo] = [$weekTo, $weekFrom];
        }

        $exerciseName = trim((string) ($filters['exerciseName'] ?? ''));

        return [
            'name' => trim((string) ($data['name'] ?? '')),
            'config' => [
                'chartType' => $chartType,
                'metric' => $metric,
                'groupBy' => $groupBy,
                'series' => $series,
                'stacked' => (bool) ($data['stacked'] ?? $defaults['stacked']),
                'filters' => [
                    'mainLift' => in_array($filters['mainLift'] ?? null, self::allowedMainLiftFilters(), true)
                        ? $filters['mainLift']
                        : 'all',
                    'repFormat' => in_array($filters['repFormat'] ?? null, self::allowedRepFormats(), true)
                        ? $filters['repFormat']
                        : 'all',
                    'section' => in_array($filters['section'] ?? null, self::allowedSections(), true)
                        ? $filters['section']
                        : 'all',
                    'weekFrom' => $weekFrom,
                    'weekTo' => $weekTo,
                    'exerciseName' => $exerciseName !== '' ? $exerciseName : null,
                ],
            ],
        ];
    }

    public static function ensureCoachHasDefaultDashboard(User $coach): void
    {
        $exists = CoachStatsDashboardItem::query()
            ->where('coach_id', $coach->id)
            ->exists();

        if ($exists) {
            return;
        }

        foreach (CoachStatsDashboardItem::builtinKeys() as $index => $builtinKey) {
            CoachStatsDashboardItem::create([
                'coach_id' => $coach->id,
                'item_type' => CoachStatsDashboardItem::TYPE_BUILTIN,
                'builtin_key' => $builtinKey,
                'sort_order' => $index,
            ]);
        }
    }

    private static function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
