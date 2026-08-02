<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoachAthleteRequest;
use App\Models\AthleteProfile;
use App\Models\User;
use App\Support\BillingAccess;
use App\Support\AccountSetupUrlGenerator;
use App\Support\ActivationDelivery;
use App\Support\ReadinessFormSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CoachAthleteRosterController extends Controller
{
    public function store(StoreCoachAthleteRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $coach = $request->user();

        if (! BillingAccess::canAddAthlete($coach)) {
            if ($coach->is_demo) {
                return back()->with(
                    'error',
                    __('messages.athletes.demo_cannot_add'),
                );
            }

            $limit = BillingAccess::seatLimit($coach);

            return back()->with(
                'error',
                $limit === null
                    ? __('messages.athletes.cannot_add_with_subscription')
                    : __('messages.athletes.seat_limit_reached', ['limit' => $limit]),
            );
        }

        $first = trim($request->validated('first_name'));
        $last = trim($request->validated('last_name'));
        $displayName = trim($first.' '.$last);
        $rawEmail = $request->validated('email');
        $email = is_string($rawEmail) ? strtolower(trim($rawEmail)) : '';
        $emailPending = $email === '';

        $athlete = User::query()->create([
            'name' => $displayName,
            'email' => $emailPending ? User::pendingAthleteEmail() : $email,
            'password' => Str::password(48),
            'role' => 'athlete',
            'initial_setup_completed_at' => null,
        ]);

        AthleteProfile::query()->create([
            'user_id' => $athlete->id,
            'feedback_frequency' => $request->validated('feedback_frequency'),
        ]);

        $coach->athletes()->attach($athlete->id, ['status' => 'active']);

        ReadinessFormSupport::copyToAthlete($athlete, $coach);

        $setupUrl = AccountSetupUrlGenerator::signedSetupUrl($athlete);

        $emailSent = ActivationDelivery::sendAthleteInvitation($athlete, $coach, $setupUrl);

        return back()
            ->with('success', ActivationDelivery::athleteInvitationSuccessMessage($athlete->name, $emailSent, $emailPending))
            ->with('first_login_url', $setupUrl)
            ->with('invitation_email', $emailPending ? null : $email)
            ->with('invitation_email_sent', $emailSent)
            ->with('invited_athlete_id', $athlete->id);
    }

    public function resendInvitation(Request $request, User $athlete): RedirectResponse
    {
        $this->authorize('detachFromRoster', $athlete);

        if ($athlete->initial_setup_completed_at !== null) {
            return redirect()
                ->route('athletes.index')
                ->with('error', __('messages.athletes.already_activated'));
        }

        $coach = $request->user();
        $setupUrl = AccountSetupUrlGenerator::signedSetupUrl($athlete);

        $emailSent = ActivationDelivery::sendAthleteInvitation($athlete, $coach, $setupUrl);

        $label = $athlete->displayEmail() ?? $athlete->name;

        return back()
            ->with('success', ActivationDelivery::athleteResendSuccessMessage($label, $emailSent, $athlete->hasPendingEmail()))
            ->with('first_login_url', $setupUrl)
            ->with('invitation_email', $athlete->displayEmail())
            ->with('invitation_email_sent', $emailSent)
            ->with('invited_athlete_id', $athlete->id);
    }

    public function destroy(Request $request, User $athlete): RedirectResponse
    {
        $this->authorize('detachFromRoster', $athlete);

        $request->user()->athletes()->detach($athlete->id);

        return redirect()
            ->route('athletes.index')
            ->with('success', __('messages.athletes.removed_from_group'));
    }
}
