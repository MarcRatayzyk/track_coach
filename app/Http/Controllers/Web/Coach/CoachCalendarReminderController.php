<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\CoachCalendarReminder;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CoachCalendarReminderController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $coach = auth()->user();
        abort_unless($coach->role === 'coach', 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'event_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'athlete_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($validated['athlete_id'] ?? null) {
            $this->assertAthleteOnRoster($coach, (int) $validated['athlete_id']);
        }

        CoachCalendarReminder::create([
            'coach_id' => $coach->id,
            'title' => $validated['title'],
            'event_date' => $validated['event_date'],
            'notes' => $validated['notes'] ?? null,
            'athlete_id' => $validated['athlete_id'] ?? null,
        ]);

        return back()->with('success', 'Rappel ajouté.');
    }

    public function update(Request $request, CoachCalendarReminder $reminder): RedirectResponse
    {
        $coach = auth()->user();
        abort_unless($coach->role === 'coach' && $reminder->coach_id === $coach->id, 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'event_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'athlete_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if ($validated['athlete_id'] ?? null) {
            $this->assertAthleteOnRoster($coach, (int) $validated['athlete_id']);
        }

        $reminder->update([
            'title' => $validated['title'],
            'event_date' => $validated['event_date'],
            'notes' => $validated['notes'] ?? null,
            'athlete_id' => $validated['athlete_id'] ?? null,
        ]);

        return back()->with('success', 'Rappel mis à jour.');
    }

    public function destroy(CoachCalendarReminder $reminder): RedirectResponse
    {
        $coach = auth()->user();
        abort_unless($coach->role === 'coach' && $reminder->coach_id === $coach->id, 403);

        $reminder->delete();

        return back()->with('success', 'Rappel supprimé.');
    }

    private function assertAthleteOnRoster(User $coach, int $athleteId): void
    {
        $exists = $coach->athletes()
            ->where('users.id', $athleteId)
            ->where('users.role', 'athlete')
            ->exists();

        abort_unless($exists, 422, 'Athlète hors roster.');
    }
}
