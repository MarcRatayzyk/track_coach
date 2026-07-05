<?php

namespace App\Support;

use App\Models\AthleteProfile;
use App\Models\SessionFeedback;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class FeedbackFrequencySupport
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public static function weekBounds(CarbonInterface $date): array
    {
        $start = $date->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $end = $start->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        return [$start, $end];
    }

    public static function frequencyFor(User $athlete): string
    {
        $athlete->loadMissing('profile');

        return $athlete->profile?->feedback_frequency ?? AthleteProfile::FREQUENCY_WEEKLY;
    }

    public static function isDaily(User $athlete): bool
    {
        return self::frequencyFor($athlete) === AthleteProfile::FREQUENCY_DAILY;
    }

    public static function isWeekly(User $athlete): bool
    {
        return self::frequencyFor($athlete) === AthleteProfile::FREQUENCY_WEEKLY;
    }

    public static function hasFeedbackForSessionDay(User $athlete, CarbonInterface $date): bool
    {
        return SessionFeedback::query()
            ->where('athlete_id', $athlete->id)
            ->whereDate('session_date', $date->toDateString())
            ->exists();
    }

    public static function hasFeedbackForWeek(User $athlete, CarbonInterface $weekStart): bool
    {
        [$start, $end] = self::weekBounds($weekStart);

        return SessionFeedback::query()
            ->where('athlete_id', $athlete->id)
            ->whereDate('session_date', '>=', $start->toDateString())
            ->whereDate('session_date', '<=', $end->toDateString())
            ->exists();
    }
}
