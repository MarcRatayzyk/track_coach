<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpsertAthleteReadinessRequest;
use App\Models\AthleteReadinessEntry;
use App\Models\User;
use App\Support\ReadinessFormSupport;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;

class AthleteReadinessController extends Controller
{
    public function store(UpsertAthleteReadinessRequest $request, User $athlete): RedirectResponse
    {
        $entryDate = $request->filled('entry_date')
            ? Carbon::parse($request->string('entry_date')->toString())->toDateString()
            : now()->toDateString();

        $form = ReadinessFormSupport::ensureAthleteHasForm($athlete);
        $fields = ReadinessFormSupport::normalizeFields($form->fields ?? []);
        $values = ReadinessFormSupport::normalizeEntryValues(
            $fields,
            $request->validated('values') ?? [],
        );

        AthleteReadinessEntry::query()->updateOrCreate(
            [
                'athlete_id' => $athlete->id,
                'entry_date' => $entryDate,
            ],
            [
                'values' => $values,
                'score' => 0,
                'sleep_score' => null,
                'stress_score' => null,
                'motivation_score' => null,
                'notes' => $request->string('notes')->toString() ?: null,
            ],
        );

        return redirect()
            ->route('athlete.dashboard')
            ->with('success', 'Check-in enregistré.');
    }
}
