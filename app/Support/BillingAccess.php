<?php

namespace App\Support;

use App\Models\User;

class BillingAccess
{
    public static function hasAppAccess(User $user): bool
    {
        if ($user->role === 'athlete') {
            $coach = $user->primaryCoach();

            return $coach ? self::coachHasAppAccess($coach) : false;
        }

        if ($user->role !== 'coach') {
            return false;
        }

        return self::coachHasAppAccess($user);
    }

    public static function coachHasAppAccess(User $coach): bool
    {
        if ($coach->is_demo) {
            return $coach->demo_expires_at !== null && $coach->demo_expires_at->isFuture();
        }

        if ($coach->onGenericTrial()) {
            return true;
        }

        return $coach->subscribed('default');
    }

    public static function status(User $coach): string
    {
        if ($coach->is_demo) {
            if ($coach->demo_expires_at && $coach->demo_expires_at->isFuture()) {
                return 'demo';
            }

            return 'demo_expired';
        }

        if ($coach->onGenericTrial()) {
            return 'trial';
        }

        if ($coach->subscribed('default')) {
            return 'subscribed';
        }

        if ($coach->hasExpiredGenericTrial()) {
            return 'trial_expired';
        }

        return 'inactive';
    }

    /**
     * Max active athletes the coach may have. null = unlimited. 0 = none allowed.
     */
    public static function seatLimit(User $coach): ?int
    {
        if ($coach->is_demo) {
            return 0;
        }

        if ($coach->onGenericTrial()) {
            return null;
        }

        $planKey = BillingPlans::currentPlanKey($coach);

        return BillingPlans::seatLimitForPlan($planKey);
    }

    public static function canAddAthlete(User $coach): bool
    {
        $limit = self::seatLimit($coach);

        if ($limit === null) {
            return true;
        }

        if ($limit === 0) {
            return false;
        }

        return $coach->activeAthleteCount() < $limit;
    }

    /**
     * @return array<string, mixed>
     */
    public static function sharedProps(?User $user): ?array
    {
        if (! $user || $user->role !== 'coach') {
            return null;
        }

        $planKey = BillingPlans::currentPlanKey($user);
        $plan = $planKey ? BillingPlans::get($planKey) : null;
        $athleteCount = $user->activeAthleteCount();
        $requiredPlan = BillingPlans::requiredPlanKeyForCount(max(1, $athleteCount));

        return [
            'hasAccess' => self::coachHasAppAccess($user),
            'status' => self::status($user),
            'trialEndsAt' => optional($user->trial_ends_at)?->toIso8601String(),
            'demoExpiresAt' => optional($user->demo_expires_at)?->toIso8601String(),
            'isDemo' => (bool) $user->is_demo,
            'plan' => $planKey,
            'planName' => $plan['name'] ?? null,
            'athleteCount' => $athleteCount,
            'seatLimit' => self::seatLimit($user),
            'requiredPlan' => $requiredPlan,
            'canAddAthlete' => self::canAddAthlete($user),
        ];
    }
}
