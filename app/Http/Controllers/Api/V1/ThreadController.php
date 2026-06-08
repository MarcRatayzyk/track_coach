<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\StoreThreadRequest;
use App\Models\Message;
use App\Models\MessageThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThreadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $threads = MessageThread::query()
            ->where('coach_id', $user->id)
            ->orWhere('athlete_id', $user->id)
            ->with(['coach', 'athlete'])
            ->latest()
            ->paginate(20);

        return response()->json($threads);
    }

    public function store(StoreThreadRequest $request): JsonResponse
    {
        $thread = MessageThread::firstOrCreate([
            'coach_id' => $request->user()->id,
            'athlete_id' => $request->integer('athlete_id'),
        ]);

        return response()->json($thread, 201);
    }

    public function messages(Request $request, MessageThread $thread): JsonResponse
    {
        $this->authorize('view', $thread);

        $messages = $thread->messages()->with('sender')->latest()->paginate(50);

        return response()->json($messages);
    }

    public function storeMessage(StoreMessageRequest $request, MessageThread $thread): JsonResponse
    {
        $this->authorize('sendMessage', $thread);

        $message = Message::create([
            'thread_id' => $thread->id,
            'sender_id' => $request->user()->id,
            'content' => $request->string('content')->toString(),
        ]);

        $thread->touch();

        MessageSent::dispatch($message);

        return response()->json($message, 201);
    }
}
