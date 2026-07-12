<?php

namespace App\Support;

use App\Models\PersonalRecord;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;

class AthleteFunStatsSupport
{
    /**
     * @return array<string, mixed>
     */
    public static function forAthlete(User $athlete, ?string $followUpStartedAt = null): array
    {
        $today = now()->copy()->startOfDay();
        $yearStart = $today->copy()->startOfYear();

        $sessions = TrainingSession::query()
            ->where('athlete_id', $athlete->id)
            ->orderBy('session_date')
            ->get(['session_date', 'items']);

        $sessionDates = $sessions
            ->pluck('session_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->sort()
            ->values();

        $prThisYear = PersonalRecord::query()
            ->where('athlete_id', $athlete->id)
            ->whereDate('reference_date', '>=', $yearStart->toDateString())
            ->count();

        $followUpStart = $followUpStartedAt
            ? Carbon::parse($followUpStartedAt)->startOfDay()
            : null;

        $prSinceCoaching = $followUpStart
            ? PersonalRecord::query()
                ->where('athlete_id', $athlete->id)
                ->whereDate('reference_date', '>=', $followUpStart->toDateString())
                ->count()
            : null;

        $highRpeSessionsThisMonth = $sessions
            ->filter(function (TrainingSession $session) use ($today): bool {
                $sessionDate = Carbon::parse($session->session_date)->startOfDay();
                if ($sessionDate->month !== $today->month || $sessionDate->year !== $today->year) {
                    return false;
                }

                return self::sessionHasRpeAtLeast($session->items ?? [], 9);
            })
            ->count();

        $latestPr = $athlete->latestPr;
        $gainSinceCoaching = null;
        if ($followUpStart && $latestPr) {
            $baseline = PersonalRecord::query()
                ->where('athlete_id', $athlete->id)
                ->whereDate('reference_date', '>=', $followUpStart->toDateString())
                ->orderBy('reference_date')
                ->first();

            if ($baseline) {
                $baselineTotal = (int) $baseline->squat + (int) $baseline->bench + (int) $baseline->deadlift;
                $currentTotal = (int) $latestPr->squat + (int) $latestPr->bench + (int) $latestPr->deadlift;
                $gainSinceCoaching = max(0, $currentTotal - $baselineTotal);
            }
        }

        return [
            'session_streak' => self::currentStreak($sessionDates, $today),
            'pr_count_year' => $prThisYear,
            'pr_count_since_coaching' => $prSinceCoaching,
            'high_rpe_sessions_month' => $highRpeSessionsThisMonth,
            'total_gain_kg_since_coaching' => $gainSinceCoaching,
            'logged_sessions_total' => $sessionDates->count(),
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, string>  $sessionDates
     */
    private static function currentStreak($sessionDates, Carbon $today): int
    {
        if ($sessionDates->isEmpty()) {
            return 0;
        }

        $dateSet = $sessionDates->flip();
        $cursor = $today->copy();
        $streak = 0;

        while ($dateSet->has($cursor->toDateString())) {
            $streak += 1;
            $cursor->subDay();
        }

        if ($streak === 0 && $dateSet->has($today->copy()->subDay()->toDateString())) {
            $cursor = $today->copy()->subDay();
            while ($dateSet->has($cursor->toDateString())) {
                $streak += 1;
                $cursor->subDay();
            }
        }

        return $streak;
    }

    /**
     * @param  array<int, mixed>  $items
     */
    private static function sessionHasRpeAtLeast(array $items, float $threshold): bool
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $rpe = isset($item['rpe']) ? (float) $item['rpe'] : null;
            if ($rpe !== null && $rpe >= $threshold) {
                return true;
            }
        }

        return false;
    }
}
