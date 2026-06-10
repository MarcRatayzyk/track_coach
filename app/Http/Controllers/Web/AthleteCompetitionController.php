<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCompetitionMatchPlanRequest;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class AthleteCompetitionController extends Controller
{
    public function updateMatchPlan(
        UpdateCompetitionMatchPlanRequest $request,
        User $athlete,
        Competition $competition,
    ): RedirectResponse {
        $this->authorize('proposeCompetitionMatchPlan', $athlete);

        if ($competition->athlete_id !== $athlete->id) {
            abort(404);
        }

        if ($competition->competition_date?->toDateString() < now()->toDateString()) {
            abort(403);
        }

        $competition->update($request->matchPlanPayload());

        return back()->with('success', 'Plan de match enregistré.');
    }
}
