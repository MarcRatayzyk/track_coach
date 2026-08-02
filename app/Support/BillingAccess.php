<?php

namespace App\Support;

use App\Mail\CoachTrialStartedMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

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

        if ($coach->subscribed('default')) {
            return true;
        }

        return $coach->onGenericTrial();
    }

    public static function status(User $coach): string
    {
        if ($coach->is_demo) {
            if ($coach->demo_expires_at && $coach->demo_expires_at->isFuture()) {
                return 'demo';
            }

            return 'demo_expired';
        }

        if ($coach->subscribed('default')) {
            return 'subscribed';
        }

        if ($coach->onGenericTrial()) {
            return 'trial';
        }

        // Essai réellement consommé (pas juste marqué expiré à l'inscription abonnement).
        if ($coach->hasExpiredGenericTrial() && ! self::trialWasNeverGranted($coach)) {
            return 'trial_expired';
        }

        return 'inactive';
    }

    /**
     * Un seul essai par e-mail : jamais démarré, ou essai fictif (inscription « s'abonner » sans paiement).
     */
    public static function canStartGenericTrial(User $coach): bool
    {
        if ($coach->role !== 'coach' || $coach->is_demo) {
            return false;
        }

        if ($coach->subscribed('default')) {
            return false;
        }

        if ($coach->onGenericTrial()) {
            return false;
        }

        if ($coach->trial_ends_at === null) {
            return true;
        }

        // Ancien bug : trial_ends_at = now() dès l'inscription via s'abonner.
        return self::trialWasNeverGranted($coach);
    }

    /**
     * True si trial_ends_at a été posé sans réelle période d'essai (fin ≈ création du compte).
     */
    public static function trialWasNeverGranted(User $coach): bool
    {
        if ($coach->trial_ends_at === null || $coach->created_at === null) {
            return false;
        }

        if ($coach->onGenericTrial()) {
            return false;
        }

        return $coach->trial_ends_at->lte($coach->created_at->copy()->addMinutes(10));
    }

    /**
     * @return array{ok: bool, trial_days: int, message: string}
     */
    public static function startGenericTrial(User $coach, bool $sendMail = true): array
    {
        $trialDays = (int) config('billing.trial_days', 14);

        if (! self::canStartGenericTrial($coach)) {
            if ($coach->onGenericTrial()) {
                return [
                    'ok' => true,
                    'trial_days' => $trialDays,
                    'message' => __('messages.billing.trial_already_active'),
                ];
            }

            if ($coach->hasExpiredGenericTrial() && ! self::trialWasNeverGranted($coach)) {
                return [
                    'ok' => false,
                    'trial_days' => $trialDays,
                    'message' => __('messages.billing.trial_already_used', ['days' => $trialDays]),
                ];
            }

            if ($coach->subscribed('default')) {
                return [
                    'ok' => false,
                    'trial_days' => $trialDays,
                    'message' => __('messages.billing.already_subscribed'),
                ];
            }

            return [
                'ok' => false,
                'trial_days' => $trialDays,
                'message' => __('messages.billing.trial_cannot_start'),
            ];
        }

        $coach->forceFill([
            'trial_ends_at' => now()->addDays($trialDays),
        ])->save();

        if ($sendMail) {
            MailSendSupport::attempt(
                fn () => Mail::to($coach)->send(new CoachTrialStartedMail(
                    $coach,
                    $trialDays,
                    $coach->trial_ends_at?->timezone(config('app.timezone'))->format('d/m/Y') ?? '',
                    route('dashboard'),
                )),
            );
        }

        return [
            'ok' => true,
            'trial_days' => $trialDays,
            'message' => __('messages.billing.trial_activated', ['days' => $trialDays]),
        ];
    }

    /**
     * Max active athletes the coach may have. null = unlimited. 0 = none allowed.
     */
    public static function seatLimit(User $coach): ?int
    {
        if ($coach->is_demo) {
            return 0;
        }

        if ($coach->subscribed('default')) {
            $planKey = BillingPlans::currentPlanKey($coach);

            return BillingPlans::seatLimitForPlan($planKey);
        }

        if ($coach->onGenericTrial()) {
            return (int) config('billing.trial_max_athletes', 15);
        }

        return 0;
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
        if (! $user) {
            return null;
        }

        if ($user->role === 'athlete') {
            return [
                'hasAccess' => self::hasAppAccess($user),
            ];
        }

        if ($user->role !== 'coach') {
            return null;
        }

        $planKey = BillingPlans::currentPlanKey($user);
        $plan = $planKey ? BillingPlans::get($planKey) : null;
        $athleteCount = $user->activeAthleteCount();
        $requiredPlan = BillingPlans::requiredPlanKeyForCount(max(1, $athleteCount));

        return [
            'hasAccess' => self::coachHasAppAccess($user),
            'status' => self::status($user),
            'canStartTrial' => self::canStartGenericTrial($user),
            'trialEndsAt' => self::trialWasNeverGranted($user)
                ? null
                : optional($user->trial_ends_at)?->toIso8601String(),
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
