<?php

namespace App\Support;

use App\Models\Competition;

class CompetitionLiveSupport
{
    /**
     * @return array<string, mixed>
     */
    public static function initialState(Competition $competition): array
    {
        if (is_array($competition->live_state) && $competition->live_state !== []) {
            return $competition->live_state;
        }

        $attempts = [
            'squat' => [],
            'bench' => [],
            'deadlift' => [],
        ];

        $plan = MatchPlanData::normalize($competition->match_plan_data);

        if (($plan['mode'] ?? '') === 'structured') {
            $scenario = $plan['scenarios'][0] ?? null;

            if (is_array($scenario)) {
                foreach (MatchPlanData::LIFTS as $lift) {
                    $planned = $scenario['lifts'][$lift] ?? [];

                    for ($i = 1; $i <= 3; $i++) {
                        $weight = $planned["attempt{$i}"] ?? null;

                        if ($weight !== null) {
                            $attempts[$lift][] = [
                                'n' => $i,
                                'weight' => $weight,
                                'success' => null,
                                'timestamp' => null,
                            ];
                        }
                    }
                }
            }
        }

        foreach (MatchPlanData::LIFTS as $lift) {
            if ($attempts[$lift] === []) {
                for ($i = 1; $i <= 3; $i++) {
                    $attempts[$lift][] = [
                        'n' => $i,
                        'weight' => null,
                        'success' => null,
                        'timestamp' => null,
                    ];
                }
            }
        }

        return [
            'status' => 'warming',
            'attempts' => $attempts,
            'current_lift' => 'squat',
            'current_attempt' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function present(Competition $competition): array
    {
        $state = self::initialState($competition);

        return [
            'id' => $competition->id,
            'name' => $competition->name,
            'competition_date' => $competition->competition_date?->toDateString(),
            'athlete_id' => $competition->athlete_id,
            'live_started_at' => $competition->live_started_at?->toIso8601String(),
            'live_ended_at' => $competition->live_ended_at?->toIso8601String(),
            'live_state' => $state,
            'total_gl' => self::goodLiftTotal($state),
        ];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function goodLiftTotal(array $state): float
    {
        $sum = 0.0;

        foreach (MatchPlanData::LIFTS as $lift) {
            $best = 0.0;

            foreach ($state['attempts'][$lift] ?? [] as $attempt) {
                if (($attempt['success'] ?? null) === true && isset($attempt['weight'])) {
                    $best = max($best, (float) $attempt['weight']);
                }
            }

            $sum += $best;
        }

        return $sum;
    }
}
