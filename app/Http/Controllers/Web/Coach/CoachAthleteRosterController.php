<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoachAthleteRequest;
use App\Mail\AthleteInvitationMail;
use App\Models\AthleteProfile;
use App\Models\User;
use App\Support\AccountSetupUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CoachAthleteRosterController extends Controller
{
    public function store(StoreCoachAthleteRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $coach = $request->user();

        $first = trim($request->validated('first_name'));
        $last = trim($request->validated('last_name'));
        $displayName = trim($first.' '.$last);

        $athlete = User::query()->create([
            'name' => $displayName,
            'email' => $request->validated('email'),
            'password' => Str::password(48),
            'role' => 'athlete',
            'initial_setup_completed_at' => null,
        ]);

        AthleteProfile::query()->create([
            'user_id' => $athlete->id,
            'feedback_frequency' => $request->validated('feedback_frequency'),
        ]);

        $coach->athletes()->attach($athlete->id, ['status' => 'active']);

        $setupUrl = AccountSetupUrlGenerator::signedSetupUrl($athlete);

        Mail::to($athlete)->send(new AthleteInvitationMail($athlete, $coach, $setupUrl));

        return redirect()
            ->route('athletes.index')
            ->with('success', "Invitation envoyée par e-mail à {$athlete->email}. L’athlète pourra choisir son mot de passe et compléter son profil à la première visite.")
            ->with('first_login_url', $setupUrl)
            ->with('invited_athlete_id', $athlete->id);
    }

    public function resendInvitation(Request $request, User $athlete): RedirectResponse
    {
        $this->authorize('detachFromRoster', $athlete);

        if ($athlete->initial_setup_completed_at !== null) {
            return redirect()
                ->route('athletes.index')
                ->with('error', 'Ce compte est déjà activé.');
        }

        $coach = $request->user();
        $setupUrl = AccountSetupUrlGenerator::signedSetupUrl($athlete);

        Mail::to($athlete)->send(new AthleteInvitationMail($athlete, $coach, $setupUrl));

        return redirect()
            ->route('athletes.index')
            ->with('success', "Invitation renvoyée à {$athlete->email}.")
            ->with('first_login_url', $setupUrl)
            ->with('invited_athlete_id', $athlete->id);
    }

    public function destroy(Request $request, User $athlete): RedirectResponse
    {
        $this->authorize('detachFromRoster', $athlete);

        $request->user()->athletes()->detach($athlete->id);

        return redirect()
            ->route('athletes.index')
            ->with('success', 'Athlète retiré de ton groupe. Son compte existe toujours pour une éventuelle réassociation.');
    }
}
