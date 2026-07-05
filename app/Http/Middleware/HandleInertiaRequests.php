<?php

namespace App\Http\Middleware;

use App\Models\Exercise;
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
            'auth' => [
                'user' => fn () => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'first_login_url' => fn () => $request->session()->get('first_login_url'),
            ],
            'messagingInbox' => fn () => match ($request->user()?->role) {
                'athlete' => MessagingInboxSupport::athleteInboxSummary($request->user()),
                'coach' => MessagingInboxSupport::coachInboxSummary($request->user()),
                default => null,
            },
            'exerciseLibrary' => fn () => $request->user()?->role === 'coach'
                ? Exercise::query()->forCoach($request->user())->with('variants')->orderBy('name')->get()
                : [],
        ]);
    }
}
