<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Competition;
use App\Models\User;
use App\Support\CompetitionLiveSupport;
use App\Support\MatchPlanData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CompetitionLiveController extends Controller
{
    public function show(User $athlete, Competition $competition): Response
    {
        abort_if($competition->athlete_id !== $athlete->id, 404);
        $this->authorize('viewLive', $competition);

        $competition->load('athlete:id,name');

        return Inertia::render('CompetitionLivePage', [
            'athlete' => [
                'id' => $athlete->id,
                'name' => $athlete->name,
            ],
            'competition' => CompetitionLiveSupport::present($competition),
            'liftLabels' => MatchPlanData::LIFT_LABELS,
        ]);
    }

    public function update(Request $request, User $athlete, Competition $competition): RedirectResponse
    {
        abort_if($competition->athlete_id !== $athlete->id, 404);
        $this->authorize('updateLive', $competition);

        $validated = $request->validate([
            'action' => ['nullable', Rule::in(['start', 'end'])],
            'live_state' => ['nullable', 'array'],
            'live_state.status' => ['nullable', Rule::in(['warming', 'live', 'done'])],
            'live_state.current_lift' => ['nullable', Rule::in(MatchPlanData::LIFTS)],
            'live_state.current_attempt' => ['nullable', 'integer', 'min:1', 'max:3'],
            'live_state.attempts' => ['nullable', 'array'],
        ]);

        if (($validated['action'] ?? null) === 'start') {
            $competition->forceFill([
                'live_started_at' => $competition->live_started_at ?? now(),
                'live_state' => array_merge(
                    CompetitionLiveSupport::initialState($competition),
                    ['status' => 'live'],
                ),
            ])->save();
        } elseif (($validated['action'] ?? null) === 'end') {
            $state = $competition->live_state ?? CompetitionLiveSupport::initialState($competition);
            $state['status'] = 'done';
            $competition->forceFill([
                'live_state' => $state,
                'live_ended_at' => now(),
            ])->save();
        } elseif (isset($validated['live_state'])) {
            $competition->forceFill([
                'live_state' => $validated['live_state'],
            ])->save();
        }

        return back();
    }
}
