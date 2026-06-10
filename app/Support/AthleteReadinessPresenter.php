<?php

namespace App\Support;

use App\Models\AthleteReadinessEntry;
use App\Models\User;

class AthleteReadinessPresenter
{
    /**
     * @return array{
     *     todayReadiness: array<string, mixed>|null,
     *     readinessRecent: list<array<string, mixed>>,
     * }
     */
    public static function forAthlete(User $athlete, int $recentDays = 365): array
    {
        $today = now()->toDateString();
        $readinessStart = now()->copy()->subDays($recentDays)->toDateString();

        $readinessRecent = AthleteReadinessEntry::query()
            ->where('athlete_id', $athlete->id)
            ->whereDate('entry_date', '>=', $readinessStart)
            ->whereDate('entry_date', '<=', $today)
            ->orderByDesc('entry_date')
            ->get()
            ->map(fn (AthleteReadinessEntry $entry) => self::entryPayload($entry))
            ->values()
            ->all();

        $todayReadiness = collect($readinessRecent)
            ->firstWhere('entry_date', $today);

        return [
            'todayReadiness' => $todayReadiness,
            'readinessRecent' => $readinessRecent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function entryPayload(AthleteReadinessEntry $entry): array
    {
        return [
            'entry_date' => $entry->entry_date->toDateString(),
            'score' => $entry->score,
            'sleep_score' => $entry->sleep_score,
            'stress_score' => $entry->stress_score,
            'motivation_score' => $entry->motivation_score,
            'notes' => $entry->notes,
        ];
    }
}
