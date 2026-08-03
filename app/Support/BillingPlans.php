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
        // Unmapped / unknown Stripe prices must never become "unlimited".
        // Fall back to Starter seat cap until the price ID is configured.
        if ($planKey === null) {
            return (int) (self::get(self::STARTER)['max_athletes'] ?? 15);
        }

        $plan = self::get($planKey);

        if ($plan === null) {
            return (int) (self::get(self::STARTER)['max_athletes'] ?? 15);
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

    public static function launchDiscountPercent(): int
    {
        return max(0, min(100, (int) config('billing.launch_discount_percent', 0)));
    }

    public static function discountedAmount(float $price, ?int $percent = null): float
    {
        $percent ??= self::launchDiscountPercent();
        if ($percent <= 0) {
            return round($price, 2);
        }

        $cents = (int) round($price * 100);

        return floor($cents * (100 - $percent) / 100) / 100;
    }

    /**
     * Plans payload for Inertia (includes list + sale prices).
     *
     * @return list<array<string, mixed>>
     */
    public static function forFrontend(): array
    {
        $discount = self::launchDiscountPercent();

        return array_values(array_map(function (array $plan) use ($discount): array {
            $eur = (float) ($plan['price_eur'] ?? 0);
            $usd = (float) ($plan['price_usd'] ?? $eur);

            return [
                'key' => $plan['key'] ?? null,
                'name' => $plan['name'] ?? null,
                'description' => $plan['description'] ?? null,
                'max_athletes' => $plan['max_athletes'] ?? null,
                'price_id' => $plan['price_id'] ?? null,
                'price_eur' => $eur,
                'price_usd' => $usd,
                'sale_price_eur' => self::discountedAmount($eur, $discount),
                'sale_price_usd' => self::discountedAmount($usd, $discount),
                'launch_discount_percent' => $discount,
            ];
        }, self::all()));
    }

    /**
     * Resolve the coach's effective plan key for seat limits / UI.
     */
    public static function currentPlanKey(User $coach): ?string
    {
        if ($coach->is_demo) {
            return null;
        }

        $subscription = $coach->subscription('default');

        if ($subscription && $subscription->valid()) {
            $priceId = $subscription->stripe_price
                ?? $subscription->items->first()?->stripe_price;

            return self::planKeyFromPriceId($priceId);
        }

        if ($coach->onGenericTrial()) {
            return self::STARTER;
        }

        return null;
    }
}
