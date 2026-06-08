<?php

namespace App\Support;

use App\Models\DayTableLayout;
use Illuminate\Support\Collection;

class DayTableLayoutPresenter
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function listForCoach(int $coachId): array
    {
        return DayTableLayout::query()
            ->where('coach_id', $coachId)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->map(fn (DayTableLayout $layout) => self::toArray($layout))
            ->values()
            ->all();
    }

    public static function defaultLayoutId(Collection $layouts): ?int
    {
        $default = $layouts->firstWhere('is_default', true);

        return $default?->id ?? $layouts->first()?->id;
    }

    /**
     * @return array<string, mixed>
     */
    public static function toArray(DayTableLayout $layout): array
    {
        return [
            'id' => $layout->id,
            'name' => $layout->name,
            'columns' => $layout->columns ?? [],
            'exercise_mode' => $layout->exercise_mode,
            'load_mode' => $layout->load_mode,
            'is_default' => $layout->is_default,
        ];
    }
}
