<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccountSetupRequest;
use App\Http\Requests\StoreCoachAccountSetupRequest;
use App\Models\PersonalRecord;
use App\Models\User;
use App\Support\AthleteProfileSupport;
use App\Support\AccountSetupUrlGenerator;
use App\Support\ActivationDelivery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AccountSetupController extends Controller
{
    public function show(Request $request, User $user): Response|RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, __('messages.auth.link_invalid_or_expired'));
        }

        if (! in_array($user->role, ['coach', 'athlete'], true)) {
            abort(404);
        }

        if ($user->initial_setup_completed_at !== null) {
            return redirect()
                ->route('login')
                ->with('success', __('messages.auth.account_already_active_login'));
        }

        $this->clearAuthenticatedSession($request);

        return Inertia::render('AccountSetupPage', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->displayEmail(),
                'needs_email' => $user->hasPendingEmail(),
            ],
            'role' => $user->role,
            'submitUrl' => AccountSetupUrlGenerator::signedUpdateUrl($user),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403, __('messages.auth.link_invalid_or_expired'));
        }

        if (! in_array($user->role, ['coach', 'athlete'], true)) {
            abort(404);
        }

        if ($user->initial_setup_completed_at !== null) {
            return redirect()
                ->route('login')
                ->with('success', __('messages.auth.account_already_active_signin'));
        }

        $validated = $user->role === 'coach'
            ? $request->validate((new StoreCoachAccountSetupRequest)->rules())
            : $request->validate(StoreAccountSetupRequest::rulesForUser($user));

        $user->forceFill([
            'password' => $validated['password'],
            'initial_setup_completed_at' => now(),
        ])->save();

        if ($user->role === 'athlete') {
            $athleteFill = [
                'email_verified_at' => now(),
            ];

            if ($user->hasPendingEmail()) {
                $athleteFill['email'] = $validated['email'];
            }

            $user->forceFill($athleteFill)->save();
            $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                AthleteProfileSupport::attributesFromValidated($validated),
            );

            $squat = (int) ($validated['squat'] ?? 0);
            $bench = (int) ($validated['bench'] ?? 0);
            $deadlift = (int) ($validated['deadlift'] ?? 0);

            if ($squat > 0 || $bench > 0 || $deadlift > 0) {
                PersonalRecord::create([
                    'athlete_id' => $user->id,
                    'squat' => $squat,
                    'bench' => $bench,
                    'deadlift' => $deadlift,
                    'reference_date' => now()->toDateString(),
                ]);
            }
        }

        if ($user->role === 'coach') {
            $user->coachProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'bio' => $validated['bio'] ?? null ?: null,
                    'specialties' => $validated['specialties'] ?? [],
                    'years_experience' => $validated['years_experience'] ?? null,
                    'certifications' => $validated['certifications'] ?? null ?: null,
                    'club_gym' => $validated['club_gym'] ?? null ?: null,
                ],
            );

            $emailSent = ActivationDelivery::sendCoachEmailVerification($user);

            $this->clearAuthenticatedSession($request);

            if (ActivationDelivery::usesManualLinks()) {
                $message = __('messages.auth.coach_activated_manual');
            } else {
                $message = $emailSent
                    ? __('messages.auth.coach_activated_confirm_email')
                    : __('messages.auth.coach_activated_resend_hint');
            }

            return redirect()
                ->route('login')
                ->with('success', $message);
        }

        $message = __('messages.auth.athlete_activated');

        $this->clearAuthenticatedSession($request);

        return redirect()
            ->route('login')
            ->with('success', $message);
    }

    private function clearAuthenticatedSession(Request $request): void
    {
        if (! Auth::check()) {
            return;
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
