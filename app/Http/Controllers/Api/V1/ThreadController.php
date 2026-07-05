<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\SendMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\StoreThreadRequest;
use App\Models\MessageThread;
use App\Models\User;
use App\Support\MessagingInboxSupport;
use App\Support\MessagePresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThreadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $threads = MessageThread::query()
            ->where(function ($query) use ($user): void {
                $query->where('coach_id', $user->id)
                    ->orWhere('athlete_id', $user->id);
            })
            ->with(['coach:id,name', 'athlete:id,name'])
            ->orderedForInbox($user)
            ->paginate(20);

        return response()->json([
            'data' => $threads->getCollection()
                ->map(fn (MessageThread $thread) => MessagingInboxSupport::threadListItem($thread, $user))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $threads->currentPage(),
                'last_page' => $threads->lastPage(),
                'per_page' => $threads->perPage(),
                'total' => $threads->total(),
            ],
        ]);
    }

    public function inboxSummary(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'coach') {
            return response()->json(MessagingInboxSupport::coachInboxSummary($user));
        }

        return response()->json(MessagingInboxSupport::athleteInboxSummary($user) ?? [
            'thread_id' => null,
            'unread_count' => 0,
            'coach_name' => null,
        ]);
    }

    public function store(StoreThreadRequest $request): JsonResponse
    {
        $coach = $request->user();

        if ($coach->role !== 'coach') {
            abort(403, 'Seuls les coachs peuvent créer une conversation.');
        }

        $athlete = User::query()->findOrFail($request->integer('athlete_id'));

        if ($athlete->id === $coach->id) {
            abort(422, 'Impossible de créer un fil avec soi-même.');
        }

        $this->authorize('updateAthleteData', $athlete);

        $thread = MessageThread::query()->firstOrCreate([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);

        return response()->json(
            MessagingInboxSupport::threadListItem($thread, $coach),
            201,
        );
    }

    public function messages(Request $request, MessageThread $thread): JsonResponse
    {
        $this->authorize('view', $thread);

        $messages = $thread->messages()
            ->with(['sender:id,name', 'audioFiles', 'sessionFeedback.programTrainingDay'])
            ->orderBy('created_at')
            ->paginate(50);

        return response()->json([
            'data' => MessagePresenter::list($messages->items()),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
            ],
        ]);
    }

    public function markRead(Request $request, MessageThread $thread): JsonResponse
    {
        $this->authorize('view', $thread);

        $thread->markAsReadFor($request->user());

        return response()->json(['message' => 'Lu']);
    }

    public function storeMessage(
        StoreMessageRequest $request,
        MessageThread $thread,
        SendMessageAction $action,
    ): JsonResponse {
        $this->authorize('sendMessage', $thread);

        $message = $action->execute(
            $request->user(),
            $thread,
            $request->validated('content'),
            $request->file('audio_files', []),
            $request->validated('session_feedback_id'),
        );

        return response()->json(MessagePresenter::message($message), 201);
    }
}
