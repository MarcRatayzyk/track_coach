<?php

namespace App\Support;

use App\Models\AthleteReadinessEntry;
use App\Models\User;

class AthleteReadinessPresenter
{
    /**
     * @return array{
     *     readinessForm: array{fields: list<array<string, mixed>>},
     *     todayReadiness: array<string, mixed>|null,
     *     readinessRecent: list<array<string, mixed>>,
     * }
     */
    public static function forAthlete(User $athlete, int $recentDays = 365): array
    {
        $form = ReadinessFormSupport::ensureAthleteHasForm($athlete);
        $fields = ReadinessFormSupport::normalizeFields($form->fields ?? []);

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
            'readinessForm' => [
                'fields' => $fields,
            ],
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
            'values' => is_array($entry->values) ? $entry->values : [],
            'notes' => $entry->notes,
            // Legacy fields kept for old seeded rows without values JSON.
            'score' => $entry->score,
            'sleep_score' => $entry->sleep_score,
            'stress_score' => $entry->stress_score,
            'motivation_score' => $entry->motivation_score,
        ];
    }
}
