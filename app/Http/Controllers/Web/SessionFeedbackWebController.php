<?php

namespace App\Http\Controllers\Web;

use App\Actions\StoreSessionFeedbackAction;
use App\Actions\StoreSessionFeedbackReplyAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionFeedbackReplyRequest;
use App\Http\Requests\StoreSessionFeedbackRequest;
use App\Models\SessionFeedback;
use App\Services\AthleteEligibleFeedbackSessionsService;
use App\Support\SessionFeedbackPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SessionFeedbackWebController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', SessionFeedback::class);

        $user = $request->user();
        $filter = $request->query('filter', 'all');
        $activeId = $request->query('feedback') ? (int) $request->query('feedback') : null;

        if ($user->role === 'coach') {
            return $this->coachIndex($user, $filter, $activeId);
        }

        return $this->athleteIndex($user, $activeId);
    }

    public function show(Request $request, SessionFeedback $feedback): Response
    {
        $this->authorize('view', $feedback);

        return Inertia::render('SessionFeedbacksPage', [
            ...$this->pagePropsForUser($request->user(), $request->query('filter', 'all')),
            'activeFeedback' => SessionFeedbackPresenter::feedback($feedback),
        ]);
    }

    public function store(
        StoreSessionFeedbackRequest $request,
        StoreSessionFeedbackAction $action,
    ): RedirectResponse {
        $feedback = $action->execute(
            $request->user(),
            $request->validated('session_date'),
            $request->validated('athlete_notes'),
            $request->file('videos', []),
        );

        return redirect()
            ->route('feedbacks.index', ['feedback' => $feedback->id])
            ->with('success', 'Retour envoyé au coach.');
    }

    public function reply(
        StoreSessionFeedbackReplyRequest $request,
        SessionFeedback $feedback,
        StoreSessionFeedbackReplyAction $action,
    ): RedirectResponse {
        $action->execute(
            $request->user(),
            $feedback,
            $request->validated('body'),
            $request->file('audio_files', []),
        );

        return redirect()
            ->route('feedbacks.index', ['feedback' => $feedback->id])
            ->with('success', 'Réponse envoyée à l\'athlète.');
    }

    private function coachIndex($coach, string $filter, ?int $activeId): Response
    {
        $query = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->with(['athlete:id,name', 'programTrainingDay', 'athleteVideos'])
            ->orderByDesc('submitted_at');

        if ($filter === 'pending') {
            $query->where('status', SessionFeedback::STATUS_SUBMITTED);
        }

        $feedbacks = $query->limit(100)->get();
        $activeFeedback = null;

        if ($activeId !== null) {
            $active = $feedbacks->firstWhere('id', $activeId)
                ?? SessionFeedback::query()
                    ->where('coach_id', $coach->id)
                    ->whereKey($activeId)
                    ->first();

            if ($active !== null) {
                $this->authorize('view', $active);
                $activeFeedback = SessionFeedbackPresenter::feedback($active);
            }
        }

        return Inertia::render('SessionFeedbacksPage', [
            'role' => 'coach',
            'filter' => $filter,
            'feedbacks' => SessionFeedbackPresenter::list($feedbacks),
            'activeFeedback' => $activeFeedback,
            'eligibleSessions' => [],
        ]);
    }

    private function athleteIndex($athlete, ?int $activeId): Response
    {
        $feedbacks = SessionFeedback::query()
            ->where('athlete_id', $athlete->id)
            ->with(['programTrainingDay', 'athleteVideos'])
            ->orderByDesc('submitted_at')
            ->limit(100)
            ->get();

        $activeFeedback = null;
        if ($activeId !== null) {
            $active = $feedbacks->firstWhere('id', $activeId)
                ?? SessionFeedback::query()
                    ->where('athlete_id', $athlete->id)
                    ->whereKey($activeId)
                    ->first();

            if ($active !== null) {
                $this->authorize('view', $active);
                $activeFeedback = SessionFeedbackPresenter::feedback($active);
            }
        }

        $eligibleService = app(AthleteEligibleFeedbackSessionsService::class);

        return Inertia::render('SessionFeedbacksPage', [
            'role' => 'athlete',
            'filter' => 'all',
            'feedbacks' => SessionFeedbackPresenter::list($feedbacks),
            'activeFeedback' => $activeFeedback,
            'eligibleSessions' => $eligibleService->forAthlete($athlete),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function pagePropsForUser($user, string $filter): array
    {
        if ($user->role === 'coach') {
            $feedbacks = SessionFeedback::query()
                ->where('coach_id', $user->id)
                ->with(['athlete:id,name', 'programTrainingDay', 'athleteVideos'])
                ->orderByDesc('submitted_at')
                ->limit(100)
                ->get();

            if ($filter === 'pending') {
                $feedbacks = $feedbacks->where('status', SessionFeedback::STATUS_SUBMITTED);
            }

            return [
                'role' => 'coach',
                'filter' => $filter,
                'feedbacks' => SessionFeedbackPresenter::list($feedbacks),
                'eligibleSessions' => [],
            ];
        }

        $feedbacks = SessionFeedback::query()
            ->where('athlete_id', $user->id)
            ->with(['programTrainingDay', 'athleteVideos'])
            ->orderByDesc('submitted_at')
            ->limit(100)
            ->get();

        return [
            'role' => 'athlete',
            'filter' => 'all',
            'feedbacks' => SessionFeedbackPresenter::list($feedbacks),
            'eligibleSessions' => app(AthleteEligibleFeedbackSessionsService::class)->forAthlete($user),
        ];
    }
}
