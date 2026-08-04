<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminStoryDemoPresenter;
use App\Support\AppSettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminDesignController extends Controller
{
    public function edit(): Response
    {
        $settings = AppSettingsRepository::allDesignSettings();

        return Inertia::render('Admin/AdminDesignPage', [
            'settings' => $settings,
            'demo' => [
                'wrapped' => AdminStoryDemoPresenter::wrappedSamples(),
                'awards' => AdminStoryDemoPresenter::awardsSample(
                    $settings[AppSettingsRepository::KEY_ROSTER_AWARDS_COPY],
                ),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'wrapped_athlete_theme' => ['required', 'array'],
            'wrapped_athlete_theme.default_accent' => ['required', 'string', 'max:20'],
            'wrapped_athlete_theme.squat' => ['required', 'string', 'max:20'],
            'wrapped_athlete_theme.bench' => ['required', 'string', 'max:20'],
            'wrapped_athlete_theme.deadlift' => ['required', 'string', 'max:20'],
            'roster_awards_theme' => ['required', 'array'],
            'roster_awards_theme.default_accent' => ['required', 'string', 'max:20'],
            'roster_awards_theme.steps' => ['required', 'string', 'max:20'],
            'roster_awards_theme.kcal' => ['required', 'string', 'max:20'],
            'roster_awards_theme.sommeil' => ['required', 'string', 'max:20'],
            'roster_awards_copy' => ['required', 'array'],
            'roster_awards_copy.steps' => ['required', 'array'],
            'roster_awards_copy.steps.eyebrow' => ['required', 'string', 'max:120'],
            'roster_awards_copy.steps.title' => ['required', 'string', 'max:160'],
            'roster_awards_copy.steps.punchline' => ['required', 'string', 'max:240'],
            'roster_awards_copy.kcal' => ['required', 'array'],
            'roster_awards_copy.kcal.eyebrow' => ['required', 'string', 'max:120'],
            'roster_awards_copy.kcal.title' => ['required', 'string', 'max:160'],
            'roster_awards_copy.kcal.punchline' => ['required', 'string', 'max:240'],
            'roster_awards_copy.sommeil' => ['required', 'array'],
            'roster_awards_copy.sommeil.eyebrow' => ['required', 'string', 'max:120'],
            'roster_awards_copy.sommeil.title' => ['required', 'string', 'max:160'],
            'roster_awards_copy.sommeil.punchline' => ['required', 'string', 'max:240'],
            'roster_awards_copy.intro_hint' => ['nullable', 'string', 'max:240'],
            'roster_awards_copy.outro_title' => ['nullable', 'string', 'max:160'],
            'roster_awards_copy.outro_subtitle' => ['nullable', 'string', 'max:240'],
            'wrapped_copy' => ['required', 'array'],
            'wrapped_copy.brand_label' => ['nullable', 'string', 'max:80'],
            'wrapped_copy.keep_going' => ['nullable', 'string', 'max:240'],
        ]);

        AppSettingsRepository::set(
            AppSettingsRepository::KEY_WRAPPED_ATHLETE_THEME,
            $validated['wrapped_athlete_theme'],
        );
        AppSettingsRepository::set(
            AppSettingsRepository::KEY_ROSTER_AWARDS_THEME,
            $validated['roster_awards_theme'],
        );
        AppSettingsRepository::set(
            AppSettingsRepository::KEY_ROSTER_AWARDS_COPY,
            $validated['roster_awards_copy'],
        );
        AppSettingsRepository::set(
            AppSettingsRepository::KEY_WRAPPED_COPY,
            $validated['wrapped_copy'],
        );

        return back()->with('success', 'Design des stories enregistré.');
    }
}
