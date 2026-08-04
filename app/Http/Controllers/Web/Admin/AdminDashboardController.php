<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\BillingAccess;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Cashier\Subscription;

class AdminDashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $now = now();

        $coachesQuery = User::query()->where('role', 'coach');
        $athletesQuery = User::query()->where('role', 'athlete');

        $activeTrials = User::query()
            ->where('role', 'coach')
            ->where('is_demo', false)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', $now)
            ->count();

        $activeDemos = User::query()
            ->where('role', 'coach')
            ->where('is_demo', true)
            ->whereNotNull('demo_expires_at')
            ->where('demo_expires_at', '>', $now)
            ->count();

        $activeSubscriptions = Subscription::query()
            ->where('type', 'default')
            ->where(function ($query): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->whereIn('stripe_status', ['active', 'trialing', 'past_due'])
            ->count();

        $recentCoaches = User::query()
            ->where('role', 'coach')
            ->withCount([
                'athletes as active_athletes_count' => fn ($q) => $q->where('coach_athlete.status', 'active'),
            ])
            ->latest('created_at')
            ->limit(8)
            ->get(['id', 'name', 'email', 'created_at', 'trial_ends_at', 'is_demo', 'demo_expires_at', 'disabled_at'])
            ->map(function (User $coach): array {
                return [
                    'id' => $coach->id,
                    'name' => $coach->name,
                    'email' => $coach->email,
                    'created_at' => $coach->created_at?->toIso8601String(),
                    'active_athletes_count' => (int) $coach->active_athletes_count,
                    'billing_status' => BillingAccess::status($coach),
                    'disabled' => $coach->isDisabled(),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('Admin/AdminDashboardPage', [
            'kpis' => [
                'coaches' => (clone $coachesQuery)->count(),
                'athletes' => (clone $athletesQuery)->count(),
                'admins' => User::query()->where('role', 'admin')->count(),
                'active_trials' => $activeTrials,
                'active_demos' => $activeDemos,
                'active_subscriptions' => $activeSubscriptions,
                'signups_7d' => User::query()->where('created_at', '>=', $now->copy()->subDays(7))->count(),
                'signups_30d' => User::query()->where('created_at', '>=', $now->copy()->subDays(30))->count(),
                'disabled' => User::query()->whereNotNull('disabled_at')->count(),
            ],
            'recentCoaches' => $recentCoaches,
        ]);
    }
}
