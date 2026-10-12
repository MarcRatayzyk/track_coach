<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompetitionRequest;
use App\Http\Requests\StorePersonalRecordRequest;
use App\Http\Requests\UpdateAthleteProfileRequest;
use App\Http\Requests\UpdateCompetitionRequest;
use App\Http\Requests\UpdateOwnAthleteProfileRequest;
use App\Http\Requests\UpsertTrainingSessionRequest;
use App\Models\Competition;
use App\Models\PersonalRecord;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\AthleteProfileSupport;
use App\Support\TrainingSessionSupport;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            ->with('success', __('messages.athletes.profile_updated'));
    }

    public function updateOwnProfile(UpdateOwnAthleteProfileRequest $request, User $athlete): RedirectResponse
    {
        $validated = $request->validated();

        $athlete->profile()->updateOrCreate(
            ['user_id' => $athlete->id],
            AthleteProfileSupport::attributesFromValidated($validated),
        );

        return back()->with('success', __('messages.athletes.profile_updated_self'));
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

        return $redirect->with('success', __('messages.records.pr_added'));
    }

    public function storeCompetition(StoreCompetitionRequest $request, User $athlete): RedirectResponse
    {
        $this->authorizeCompetitionMutation($request->user(), $athlete);

        Competition::create([
            'athlete_id' => $athlete->id,
            ...$request->competitionPayload(),
        ]);

        return $this->competitionRedirect($request->user(), $athlete)
            ->with('success', __('messages.competitions.added'));
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
            ->with('success', __('messages.competitions.updated'));
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
            ->with('success', __('messages.competitions.deleted'));
    }

    public function storeTrainingSession(UpsertTrainingSessionRequest $request, User $athlete): RedirectResponse
    {
        $this->authorizeTrainingSession($request, $athlete);

        $session = new TrainingSession(['athlete_id' => $athlete->id]);
        TrainingSessionSupport::applyValidated($session, $request->validated());
        $session->save();

        return $this->trainingSessionRedirect($request->user(), $athlete)
            ->with('success', __('messages.sessions.saved'));
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
            ->with('success', __('messages.sessions.updated'));
    }

    public function startTrainingSessionTimer(Request $request, User $athlete): RedirectResponse
    {
        $this->authorizeAthleteData($request->user(), $athlete);
        $session = $this->resolveTimerSession($request, $athlete, create: true);

        if ($session->finished_at === null && $session->timer_started_at === null) {
            $session->timer_started_at = $this->timerStartedAt($request);
            $session->save();
        }

        return back();
    }

    public function stopTrainingSessionTimer(Request $request, User $athlete): RedirectResponse
    {
        $this->authorizeAthleteData($request->user(), $athlete);
        $session = $this->resolveTimerSession($request, $athlete, create: false);

        if ($session !== null && $session->finished_at === null) {
            TrainingSessionSupport::closeOpenTimer($session);
            $session->save();
        }

        return back();
    }

    public function finishTrainingSession(Request $request, User $athlete): RedirectResponse
    {
        $this->authorizeAthleteData($request->user(), $athlete);
        $session = $this->resolveTimerSession($request, $athlete, create: true);

        if ($session->finished_at === null) {
            if ($session->timer_started_at === null && $request->filled('started_at')) {
                $session->timer_started_at = $this->timerStartedAt($request);
            }
            TrainingSessionSupport::closeOpenTimer($session);
            $session->finished_at = now();
            $session->save();
        }

        return back();
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
            ->with('success', __('messages.sessions.deleted'));
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
        $this->authorizeAthleteData($request->user(), $athlete);
    }

    private function authorizeAthleteData(User $user, User $athlete): void
    {
        if ($user->role === 'coach') {
            $this->authorize('updateAthleteData', $athlete);
        } elseif ($user->id !== $athlete->id) {
            abort(403);
        }
    }

    private function resolveTimerSession(Request $request, User $athlete, bool $create): ?TrainingSession
    {
        $validated = $request->validate([
            'session_date' => ['required', 'date'],
            'main_lift' => ['nullable', Rule::in(['squat', 'bench', 'deadlift'])],
            'session_label' => ['nullable', 'string', 'max:255'],
            'started_at' => ['nullable', 'date'],
        ]);

        $session = TrainingSession::query()
            ->where('athlete_id', $athlete->id)
            ->whereDate('session_date', $validated['session_date'])
            ->orderByDesc('id')
            ->first();

        if ($session !== null || ! $create) {
            return $session;
        }

        $session = new TrainingSession([
            'athlete_id' => $athlete->id,
            'session_date' => $validated['session_date'],
            'session_label' => $validated['session_label'] ?? null,
            'main_lift' => $validated['main_lift'] ?? 'squat',
            'items' => [],
            'squat' => 0,
            'bench' => 0,
            'deadlift' => 0,
            'notes' => null,
            'timer_elapsed_seconds' => 0,
        ]);
        $session->save();

        return $session;
    }

    private function timerStartedAt(Request $request): Carbon
    {
        $startedAt = $request->filled('started_at')
            ? Carbon::parse($request->string('started_at')->toString())
            : now();

        if ($startedAt->greaterThan(now())) {
            return now();
        }

        $earliest = now()->subSeconds(TrainingSessionSupport::MAX_TIMER_SEGMENT_SECONDS);
        if ($startedAt->lessThan($earliest)) {
            return $earliest;
        }

        return $startedAt;
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
        return back();
    }
}
