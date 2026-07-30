<?php

namespace App\Support;

use App\Models\User;

class BillingPlans
{
    public const STARTER = 'starter';

    public const GROWTH = 'growth';

    public const SCALE = 'scale';

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return config('billing.plans', []);
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    public static function requiredPlanKeyForCount(int $athleteCount): string
    {
        if ($athleteCount <= 15) {
            return self::STARTER;
        }

        if ($athleteCount <= 40) {
            return self::GROWTH;
        }

        return self::SCALE;
    }

    /**
     * Rank used to compare plans (higher = more seats).
     */
    public static function rank(string $planKey): int
    {
        return match ($planKey) {
            self::STARTER => 1,
            self::GROWTH => 2,
            self::SCALE => 3,
            default => 0,
        };
    }

    public static function meetsRequirement(string $chosenPlan, string $requiredPlan): bool
    {
        return self::rank($chosenPlan) >= self::rank($requiredPlan);
    }

    public static function seatLimitForPlan(?string $planKey): ?int
    {
        if ($planKey === null) {
            return null;
        }

        $plan = self::get($planKey);

        if ($plan === null) {
            return null;
        }

        $max = $plan['max_athletes'] ?? null;

        return $max === null ? null : (int) $max;
    }

    public static function planKeyFromPriceId(?string $priceId): ?string
    {
        if (! $priceId) {
            return null;
        }

        foreach (self::all() as $key => $plan) {
            if (! empty($plan['price_id']) && $plan['price_id'] === $priceId) {
                return $key;
            }
        }

        return null;
    }

    public static function priceIdForPlan(string $planKey): ?string
    {
        return self::get($planKey)['price_id'] ?? null;
    }

    /**
     * Resolve the coach's effective plan key for seat limits / UI.
     */
    public static function currentPlanKey(User $coach): ?string
    {
        if ($coach->is_demo) {
            return null;
        }

        if ($coach->onGenericTrial()) {
            return self::SCALE;
        }

        $subscription = $coach->subscription('default');

        if (! $subscription || ! $subscription->valid()) {
            return null;
        }

        $priceId = $subscription->stripe_price
            ?? $subscription->items->first()?->stripe_price;

        return self::planKeyFromPriceId($priceId);
    }
}
