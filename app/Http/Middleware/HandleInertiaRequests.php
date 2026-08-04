<?php

namespace App\Http\Middleware;

use App\Models\Exercise;
use App\Support\AppSettingsRepository;
use App\Support\AuthSidebarSupport;
use App\Support\ActivationDelivery;
use App\Support\BillingAccess;
use App\Support\MessagingInboxSupport;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            'locale' => fn () => app()->getLocale(),
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                    'is_demo' => (bool) $request->user()->is_demo,
                ] : null,
                'sidebarProfile' => fn () => $request->user()
                    ? AuthSidebarSupport::profileLinkForUser($request->user())
                    : null,
                'coach' => fn () => $request->user()?->role === 'athlete'
                    ? AuthSidebarSupport::coachSummaryForAthlete($request->user())
                    : null,
            ],
            'billing' => fn () => BillingAccess::sharedProps($request->user()),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'first_login_url' => fn () => $request->session()->get('first_login_url'),
                'invitation_email' => fn () => $request->session()->get('invitation_email'),
                'invitation_email_sent' => fn () => $request->session()->get('invitation_email_sent'),
                'demo_welcome' => fn () => $request->session()->get('demo_welcome'),
            ],
            'appConfig' => [
                'name' => config('app.name'),
                'manualActivationLinks' => fn () => ActivationDelivery::usesManualLinks(),
            ],
            'legal' => fn () => config('legal'),
            'storyThemes' => function () use ($request) {
                $user = $request->user();
                if (! $user) {
                    return null;
                }

                return AppSettingsRepository::storyThemesPayload();
            },
            'messagingInbox' => function () use ($request) {
                $user = $request->user();
                if (! $user || ! BillingAccess::hasAppAccess($user)) {
                    return null;
                }

                return match ($user->role) {
                    'athlete' => MessagingInboxSupport::athleteInboxSummary($user),
                    'coach' => MessagingInboxSupport::coachInboxSummary($user),
                    default => null,
                };
            },
            'exerciseLibrary' => function () use ($request) {
                $user = $request->user();
                if (! $user || $user->role !== 'coach' || ! BillingAccess::coachHasAppAccess($user)) {
                    return [];
                }

                return Exercise::query()->forCoach($user)->with('variants')->orderBy('name')->get();
            },
        ]);
    }
}
