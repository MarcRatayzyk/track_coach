<?php

namespace App\Http\Controllers\Web;

use App\Actions\StoreSessionFeedbackAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionFeedbackRequest;
use App\Models\SessionFeedback;
use App\Services\AthleteEligibleFeedbackSessionsService;
use App\Support\FeedbackFrequencySupport;
use App\Support\SessionFeedbackPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

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
            'feedbackFrequency' => null,
            'uploadLimits' => $this->uploadLimits(),
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
            'feedbackFrequency' => FeedbackFrequencySupport::frequencyFor($athlete),
            'uploadLimits' => $this->uploadLimits(),
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
                'feedbackFrequency' => null,
                'uploadLimits' => $this->uploadLimits(),
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
            'feedbackFrequency' => FeedbackFrequencySupport::frequencyFor($user),
            'uploadLimits' => $this->uploadLimits(),
        ];
    }

    /**
     * @return array{maxFiles:int, maxFileBytes:int}
     */
    private function uploadLimits(): array
    {
        $uploadMax = $this->bytesFromIni((string) ini_get('upload_max_filesize'));
        $postMax = $this->bytesFromIni((string) ini_get('post_max_size'));

        // On garde une marge sur post_max_size (il contient aussi les champs texte + multipart overhead)
        $effectiveMax = max(0, min($uploadMax, (int) floor($postMax * 0.9)));

        return [
            'maxFiles' => 3,
            'maxFileBytes' => $effectiveMax,
        ];
    }

    private function bytesFromIni(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        if (! preg_match('/^(\d+(?:\.\d+)?)\s*([KMG])?B?$/i', $value, $m)) {
            throw new InvalidArgumentException("Invalid ini size: {$value}");
        }

        $number = (float) $m[1];
        $unit = strtoupper($m[2] ?? '');

        $multiplier = match ($unit) {
            'G' => 1024 * 1024 * 1024,
            'M' => 1024 * 1024,
            'K' => 1024,
            default => 1,
        };

        return (int) floor($number * $multiplier);
    }
}
