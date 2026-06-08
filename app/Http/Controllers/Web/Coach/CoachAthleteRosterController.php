<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCoachAthleteRequest;
use App\Models\AthleteProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
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

        $setupUrl = URL::temporarySignedRoute(
            'account.setup.show',
            now()->addDays(14),
            ['user' => $athlete->id],
        );

        return redirect()
            ->route('athletes.index')
            ->with('success', 'Athlète ajouté. Envoie le lien d’activation à ton athlète : il choisira son mot de passe et complétera son profil à la première visite.')
            ->with('first_login_url', $setupUrl);
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
