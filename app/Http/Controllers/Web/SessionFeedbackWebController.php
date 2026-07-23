<?php

namespace App\Http\Controllers\Web;

use App\Actions\SendFeedbackReplyMessageAction;
use App\Actions\StoreSessionFeedbackAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSessionFeedbackRequest;
use App\Models\MessageThread;
use App\Models\SessionFeedback;
use App\Services\AthleteEligibleFeedbackSessionsService;
use App\Support\FeedbackFrequencySupport;
use App\Support\SessionFeedbackPresenter;
use App\Support\VideoUploadDisk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        $videoUploadIds = array_values(array_map(
            'intval',
            (array) $request->validated('video_upload_ids', []),
        ));

        $videoSeries = array_map(
            static fn ($id) => $id === null || $id === '' ? null : (int) $id,
            (array) $request->validated('video_series', []),
        );

        $feedback = $action->execute(
            $request->user(),
            $request->validated('session_date'),
            $request->validated('athlete_notes'),
            $request->file('videos', []) ?? [],
            $videoUploadIds,
            $videoSeries,
        );

        return redirect()
            ->route('feedbacks.index', ['feedback' => $feedback->id])
            ->with('success', 'Retour envoyé au coach.');
    }

    public function reply(
        Request $request,
        SessionFeedback $feedback,
        SendFeedbackReplyMessageAction $action,
    ): RedirectResponse {
        $this->authorize('reply', $feedback);

        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $body = trim($data['content']);
        if ($body === '') {
            throw ValidationException::withMessages([
                'content' => 'Écrivez votre retour avant de l’envoyer.',
            ]);
        }

        $thread = MessageThread::query()->firstOrCreate([
            'coach_id' => $feedback->coach_id,
            'athlete_id' => $feedback->athlete_id,
        ]);

        $action->execute($request->user(), $thread, $feedback->id, $body, []);

        return redirect()
            ->route('feedbacks.index', [
                'feedback' => $feedback->id,
                'filter' => $request->query('filter', 'all'),
            ])
            ->with('success', 'Retour envoyé à l’athlète.');
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
     * @return array{maxFiles:int, maxFileBytes:int, driver:string}
     */
    private function uploadLimits(): array
    {
        $uploadMax = $this->bytesFromIni((string) ini_get('upload_max_filesize'));
        $postMax = $this->bytesFromIni((string) ini_get('post_max_size'));

        // On garde une marge sur post_max_size (il contient aussi les champs texte + multipart overhead)
        $effectiveMax = max(0, min($uploadMax, (int) floor($postMax * 0.9)));

        return VideoUploadDisk::uploadLimits($effectiveMax);
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
