<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertAthleteReadinessRequest;
use App\Models\AthleteReadinessEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class AthleteReadinessController extends Controller
{
    public function store(UpsertAthleteReadinessRequest $request, User $athlete): RedirectResponse
    {
        $entryDate = $request->filled('entry_date')
            ? Carbon::parse($request->string('entry_date')->toString())->toDateString()
            : now()->toDateString();

        $sleep = $request->integer('sleep_score');
        $stress = $request->integer('stress_score');
        $motivation = $request->integer('motivation_score');

        AthleteReadinessEntry::query()->updateOrCreate(
            [
                'athlete_id' => $athlete->id,
                'entry_date' => $entryDate,
            ],
            [
                'sleep_score' => $sleep,
                'stress_score' => $stress,
                'motivation_score' => $motivation,
                'score' => AthleteReadinessEntry::computeScore($sleep, $stress, $motivation),
                'notes' => $request->string('notes')->toString() ?: null,
            ],
        );

        return redirect()
            ->route('athlete.dashboard')
            ->with('success', 'Readiness enregistrée.');
    }
}
