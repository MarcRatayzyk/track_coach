<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAthleteReadinessFormRequest;
use App\Http\Requests\UpdateCoachReadinessFormRequest;
use App\Models\AthleteReadinessForm;
use App\Models\User;
use App\Support\ReadinessFormSupport;
use Illuminate\Http\RedirectResponse;

class CoachReadinessFormController extends Controller
{
    public function updateTemplate(UpdateCoachReadinessFormRequest $request): RedirectResponse
    {
        $coach = $request->user();
        $fields = ReadinessFormSupport::normalizeFields($request->validated('fields'));

        $form = ReadinessFormSupport::ensureCoachHasDefaultForm($coach);
        $form->update(['fields' => $fields]);

        return back()->with('success', 'Formulaire readiness par défaut mis à jour.');
    }

    public function updateAthleteForm(UpdateAthleteReadinessFormRequest $request, User $athlete): RedirectResponse
    {
        $fields = ReadinessFormSupport::normalizeFields($request->validated('fields'));

        AthleteReadinessForm::query()->updateOrCreate(
            ['athlete_id' => $athlete->id],
            ['fields' => $fields],
        );

        return back()->with('success', 'Formulaire readiness de l\'athlète mis à jour.');
    }
}
