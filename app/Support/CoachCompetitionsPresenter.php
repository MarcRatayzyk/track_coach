<?php

namespace App\Support;

use App\Models\AthleteBodyWeightEntry;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Support\Collection;

class CoachCompetitionsPresenter
{
    /**
     * @return array{upcoming: list<array<string, mixed>>, past: list<array<string, mixed>>}
     */
    public static function forCoach(User $coach): array
    {
        $athleteIds = $coach->athletes()
            ->where('users.role', 'athlete')
            ->pluck('users.id');

        if ($athleteIds->isEmpty()) {
            return [
                'upcoming' => [],
                'past' => [],
            ];
        }

        $today = now()->toDateString();

        $latestWeights = AthleteBodyWeightEntry::query()
            ->whereIn('athlete_id', $athleteIds)
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->get()
            ->unique('athlete_id')
            ->keyBy('athlete_id');

        $upcoming = Competition::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('competition_date', '>=', $today)
            ->with(['athlete:id,name', 'athlete.profile:id,user_id,weight_category'])
            ->orderBy('competition_date')
            ->orderBy('id')
            ->get()
            ->map(fn (Competition $competition) => self::upcomingRow($competition, $latestWeights))
            ->values()
            ->all();

        $past = Competition::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('competition_date', '<', $today)
            ->with(['athlete:id,name', 'athlete.profile:id,user_id,weight_category'])
            ->orderByDesc('competition_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Competition $competition) => self::pastRow($competition, $latestWeights))
            ->values()
            ->all();

        return [
            'upcoming' => $upcoming,
            'past' => $past,
        ];
    }

    /**
     * @param  Collection<int, AthleteBodyWeightEntry>  $latestWeights
     * @return array<string, mixed>
     */
    private static function upcomingRow(Competition $competition, Collection $latestWeights): array
    {
        return [
            ...self::baseRow($competition, $latestWeights),
            'primary_scenario' => self::primaryScenario($competition->match_plan_data),
        ];
    }

    /**
     * @param  Collection<int, AthleteBodyWeightEntry>  $latestWeights
     * @return array<string, mixed>
     */
    private static function pastRow(Competition $competition, Collection $latestWeights): array
    {
        return [
            ...self::baseRow($competition, $latestWeights),
            'live_result' => self::liveResult($competition),
        ];
    }

    /**
     * @param  Collection<int, AthleteBodyWeightEntry>  $latestWeights
     * @return array<string, mixed>
     */
    private static function baseRow(Competition $competition, Collection $latestWeights): array
    {
        $athlete = $competition->athlete;
        $weightEntry = $athlete ? $latestWeights->get($athlete->id) : null;

        return [
            'id' => $competition->id,
            'name' => $competition->name,
            'competition_date' => $competition->competition_date?->toDateString(),
            'location' => $competition->location,
            'athlete' => [
                'id' => $athlete?->id,
                'name' => $athlete?->name,
                'weight_category' => $athlete?->profile?->weight_category,
                'last_body_weight' => $weightEntry
                    ? [
                        'weight_kg' => (float) $weightEntry->weight_kg,
                        'entry_date' => $weightEntry->entry_date->toDateString(),
                    ]
                    : null,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>|null  $matchPlanData
     * @return array<string, mixed>|null
     */
    private static function primaryScenario(?array $matchPlanData): ?array
    {
        $plan = MatchPlanData::normalize($matchPlanData);

        if (($plan['mode'] ?? '') !== 'structured') {
            return null;
        }

        $scenario = $plan['scenarios'][0] ?? null;
        if (! is_array($scenario)) {
            return null;
        }

        $hasAttempt = false;
        foreach (MatchPlanData::LIFTS as $lift) {
            foreach (['attempt1', 'attempt2', 'attempt3'] as $key) {
                if (($scenario['lifts'][$lift][$key] ?? null) !== null) {
                    $hasAttempt = true;
                    break 2;
                }
            }
        }

        if (! $hasAttempt) {
            return null;
        }

        $total = MatchPlanData::scenarioTotal($scenario);

        return [
            'name' => $scenario['name'],
            'lifts' => $scenario['lifts'],
            'total' => $total > 0 ? $total : null,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function liveResult(Competition $competition): ?array
    {
        if (! self::hasLiveResult($competition)) {
            return null;
        }

        $state = is_array($competition->live_state) ? $competition->live_state : [];
        $attempts = [];

        foreach (MatchPlanData::LIFTS as $lift) {
            $liftAttempts = [];
            foreach ($state['attempts'][$lift] ?? [] as $attempt) {
                if (! is_array($attempt)) {
                    continue;
                }
                $liftAttempts[] = [
                    'n' => (int) ($attempt['n'] ?? count($liftAttempts) + 1),
                    'weight' => isset($attempt['weight']) && $attempt['weight'] !== '' && $attempt['weight'] !== null
                        ? (float) $attempt['weight']
                        : null,
                    'success' => array_key_exists('success', $attempt) ? $attempt['success'] : null,
                ];
            }
            $attempts[$lift] = $liftAttempts;
        }

        return [
            'attempts' => $attempts,
            'total_gl' => CompetitionLiveSupport::goodLiftTotal($state),
            'status' => $state['status'] ?? null,
        ];
    }

    private static function hasLiveResult(Competition $competition): bool
    {
        if ($competition->live_started_at || $competition->live_ended_at) {
            return true;
        }

        $state = $competition->live_state;
        if (! is_array($state) || $state === []) {
            return false;
        }

        if (in_array($state['status'] ?? null, ['live', 'done'], true)) {
            return true;
        }

        foreach (MatchPlanData::LIFTS as $lift) {
            foreach ($state['attempts'][$lift] ?? [] as $attempt) {
                if (! is_array($attempt)) {
                    continue;
                }
                if (($attempt['success'] ?? null) !== null) {
                    return true;
                }
            }
        }

        return false;
    }
}
