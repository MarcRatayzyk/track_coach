<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCoachProfileRequest;
use App\Models\MessageThread;
use App\Models\User;
use App\Support\CoachProfilePresenter;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CoachProfileController extends Controller
{
    public function ownProfile(): Response
    {
        $coach = auth()->user();
        abort_unless($coach->role === 'coach', 403);

        $coach->loadMissing('coachProfile');

        return Inertia::render('CoachProfilePage', [
            'coach' => CoachProfilePresenter::forCoach($coach, includeStats: true),
            'canEdit' => true,
            'editableProfile' => CoachProfilePresenter::editableFields($coach->coachProfile),
            'messagingThreadId' => null,
        ]);
    }

    public function show(User $coach): Response
    {
        abort_unless($coach->role === 'coach', 404);
        $this->authorizeCoachView($coach);

        $coach->loadMissing('coachProfile');
        $viewer = auth()->user();

        $threadId = null;
        if ($viewer->role === 'athlete') {
            $threadId = MessageThread::query()
                ->where('coach_id', $coach->id)
                ->where('athlete_id', $viewer->id)
                ->value('id');
        }

        return Inertia::render('CoachProfilePage', [
            'coach' => CoachProfilePresenter::forCoach($coach, includeStats: $viewer->id === $coach->id),
            'canEdit' => $viewer->id === $coach->id,
            'editableProfile' => $viewer->id === $coach->id
                ? CoachProfilePresenter::editableFields($coach->coachProfile)
                : null,
            'messagingThreadId' => $threadId,
        ]);
    }

    public function update(UpdateCoachProfileRequest $request): RedirectResponse
    {
        $coach = auth()->user();
        abort_unless($coach->role === 'coach', 403);

        $validated = $request->validated();

        $coach->coachProfile()->updateOrCreate(
            ['user_id' => $coach->id],
            [
                'bio' => $validated['bio'] ?? null ?: null,
                'specialties' => $validated['specialties'] ?? [],
                'years_experience' => $validated['years_experience'] ?? null,
                'certifications' => $validated['certifications'] ?? null ?: null,
                'club_gym' => $validated['club_gym'] ?? null ?: null,
            ],
        );

        return back()->with('success', 'Profil coach mis à jour.');
    }

    private function authorizeCoachView(User $coach): void
    {
        $viewer = auth()->user();

        if ($viewer->id === $coach->id) {
            return;
        }

        if ($viewer->role === 'athlete') {
            $allowed = $viewer->coaches()
                ->where('users.id', $coach->id)
                ->wherePivot('status', 'active')
                ->exists();

            abort_unless($allowed, 403);

            return;
        }

        abort(403);
    }
}
