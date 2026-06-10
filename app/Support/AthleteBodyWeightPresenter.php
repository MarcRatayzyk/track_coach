<?php

namespace App\Support;

use App\Models\AthleteBodyWeightEntry;
use App\Models\User;

class AthleteBodyWeightPresenter
{
    /**
     * @return array{
     *     todayBodyWeight: array<string, mixed>|null,
     *     bodyWeightRecent: list<array<string, mixed>>,
     * }
     */
    public static function forAthlete(User $athlete, int $recentDays = 365): array
    {
        $today = now()->toDateString();
        $startDate = now()->copy()->subDays($recentDays)->toDateString();

        $bodyWeightRecent = AthleteBodyWeightEntry::query()
            ->where('athlete_id', $athlete->id)
            ->whereDate('entry_date', '>=', $startDate)
            ->whereDate('entry_date', '<=', $today)
            ->orderByDesc('entry_date')
            ->get()
            ->map(fn (AthleteBodyWeightEntry $entry) => self::entryPayload($entry))
            ->values()
            ->all();

        $todayBodyWeight = collect($bodyWeightRecent)
            ->firstWhere('entry_date', $today);

        return [
            'todayBodyWeight' => $todayBodyWeight,
            'bodyWeightRecent' => $bodyWeightRecent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function entryPayload(AthleteBodyWeightEntry $entry): array
    {
        return [
            'entry_date' => $entry->entry_date->toDateString(),
            'weight_kg' => (float) $entry->weight_kg,
        ];
    }
}
