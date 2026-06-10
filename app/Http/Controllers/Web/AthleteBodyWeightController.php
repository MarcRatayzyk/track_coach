<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertAthleteBodyWeightRequest;
use App\Models\AthleteBodyWeightEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class AthleteBodyWeightController extends Controller
{
    public function store(UpsertAthleteBodyWeightRequest $request, User $athlete): RedirectResponse
    {
        $entryDate = $request->filled('entry_date')
            ? Carbon::parse($request->string('entry_date')->toString())->toDateString()
            : now()->toDateString();

        AthleteBodyWeightEntry::query()->updateOrCreate(
            [
                'athlete_id' => $athlete->id,
                'entry_date' => $entryDate,
            ],
            [
                'weight_kg' => round((float) $request->input('weight_kg'), 2),
            ],
        );

        return redirect()
            ->route('athlete.dashboard')
            ->with('success', 'Poids du corps enregistré.');
    }
}
