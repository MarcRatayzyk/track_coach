<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompetitionRequest;
use App\Http\Requests\StorePersonalRecordRequest;
use App\Http\Requests\UpsertTrainingSessionRequest;
use App\Support\AthleteProfileSupport;
use App\Support\TrainingSessionSupport;
use App\Http\Requests\UpdateAthleteProfileRequest;
use App\Http\Requests\UpdateCompetitionRequest;
use App\Http\Requests\UpdateOwnAthleteProfileRequest;
use App\Models\Competition;
use App\Models\PersonalRecord;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AthleteDataWebController extends Controller
{
    public function updateProfile(UpdateAthleteProfileRequest $request, User $athlete): RedirectResponse
    {
        $this->authorize('updateAthleteData', $athlete);

        $validated = $request->validated();
        $attributes = AthleteProfileSupport::attributesFromValidated($validated);

        if ($attributes !== []) {
            $athlete->profile()->updateOrCreate(['user_id' => $athlete->id], $attributes);
        }

        if ($request->filled('feedback_frequency')) {
            $athlete->profile()->updateOrCreate(
                ['user_id' => $athlete->id],
                ['feedback_frequency' => $validated['feedback_frequency']],
            );
        }

        return redirect()
            ->route('athletes.show', $athlete)
            ->with('success', 'Profil athlète mis à jour.');
    }

    public function updateOwnProfile(UpdateOwnAthleteProfileRequest $request, User $athlete): RedirectResponse
    {
        $validated = $request->validated();

        $athlete->profile()->updateOrCreate(
            ['user_id' => $athlete->id],
            AthleteProfileSupport::attributesFromValidated($validated),
        );

        return back()->with('success', 'Profil mis à jour.');
    }

    public function storePr(StorePersonalRecordRequest $request, User $athlete): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === 'coach') {
            $this->authorize('updateAthleteData', $athlete);
        } else {
            $this->authorize('recordOwnPr', $athlete);
        }

        PersonalRecord::create([
            'athlete_id' => $athlete->id,
            ...$request->validated(),
        ]);

        $redirect = $user->id === $athlete->id
            ? back()
            : redirect()->route('athletes.show', $athlete);

        return $redirect->with('success', 'PR ajouté.');
    }

    public function storeCompetition(StoreCompetitionRequest $request, User $athlete): RedirectResponse
    {
        $this->authorizeCompetitionMutation($request->user(), $athlete);

        Competition::create([
            'athlete_id' => $athlete->id,
            ...$request->competitionPayload(),
        ]);

        return $this->competitionRedirect($request->user(), $athlete)
            ->with('success', 'Compétition ajoutée.');
    }

    public function updateCompetition(
        UpdateCompetitionRequest $request,
        User $athlete,
        Competition $competition,
    ): RedirectResponse {
        $this->authorizeCompetitionMutation($request->user(), $athlete);

        if ($competition->athlete_id !== $athlete->id) {
            abort(404);
        }

        $competition->update($request->competitionPayload());

        return $this->competitionRedirect($request->user(), $athlete)
            ->with('success', 'Compétition mise à jour.');
    }

    public function destroyCompetition(User $athlete, Competition $competition): RedirectResponse
    {
        $user = auth()->user();
        $this->authorizeCompetitionMutation($user, $athlete);

        if ($competition->athlete_id !== $athlete->id) {
            abort(404);
        }

        $competition->delete();

        return $this->competitionRedirect($user, $athlete)
            ->with('success', 'Compétition supprimée.');
    }

    public function storeTrainingSession(UpsertTrainingSessionRequest $request, User $athlete): RedirectResponse
    {
        $this->authorizeTrainingSession($request, $athlete);

        $session = new TrainingSession(['athlete_id' => $athlete->id]);
        TrainingSessionSupport::applyValidated($session, $request->validated());
        $session->save();

        return $this->trainingSessionRedirect($request->user(), $athlete)
            ->with('success', 'Séance enregistrée.');
    }

    public function updateTrainingSession(
        UpsertTrainingSessionRequest $request,
        User $athlete,
        TrainingSession $trainingSession,
    ): RedirectResponse {
        $this->authorizeTrainingSession($request, $athlete);

        if ($trainingSession->athlete_id !== $athlete->id) {
            abort(404);
        }

        TrainingSessionSupport::applyValidated($trainingSession, $request->validated());
        $trainingSession->save();

        return $this->trainingSessionRedirect($request->user(), $athlete)
            ->with('success', 'Séance mise à jour.');
    }

    public function destroyTrainingSession(
        User $athlete,
        TrainingSession $trainingSession,
    ): RedirectResponse {
        $user = auth()->user();

        if ($user->role === 'coach') {
            $this->authorize('updateAthleteData', $athlete);
        } elseif ($user->id !== $athlete->id) {
            abort(403);
        }

        if ($trainingSession->athlete_id !== $athlete->id) {
            abort(404);
        }

        $trainingSession->delete();

        return $this->trainingSessionRedirect($user, $athlete)
            ->with('success', 'Séance supprimée.');
    }

    private function trainingSessionRedirect(User $user, User $athlete): RedirectResponse
    {
        if ($user->id === $athlete->id) {
            return back();
        }

        return redirect()->route('athletes.show', $athlete);
    }

    private function authorizeTrainingSession(UpsertTrainingSessionRequest $request, User $athlete): void
    {
        $user = $request->user();

        if ($user->role === 'coach') {
            $this->authorize('updateAthleteData', $athlete);
        } elseif ($user->id !== $athlete->id) {
            abort(403);
        }
    }

    private function authorizeCompetitionMutation(User $user, User $athlete): void
    {
        if ($user->role === 'coach') {
            $this->authorize('updateAthleteData', $athlete);
        } else {
            $this->authorize('manageOwnCompetitions', $athlete);
        }
    }

    private function competitionRedirect(User $user, User $athlete): RedirectResponse
    {
        if ($user->id === $athlete->id) {
            return back();
        }

        return redirect()->route('athletes.show', $athlete);
    }
}
