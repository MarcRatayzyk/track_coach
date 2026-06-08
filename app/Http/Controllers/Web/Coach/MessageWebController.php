<?php

namespace App\Http\Controllers\Web\Coach;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\StoreThreadRequest;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class MessageWebController extends Controller
{
    public function storeThread(StoreThreadRequest $request): RedirectResponse
    {
        $athlete = User::query()->whereKey($request->integer('athlete_id'))->firstOrFail();
        $this->authorize('updateAthleteData', $athlete);

        $thread = MessageThread::firstOrCreate([
            'coach_id' => $request->user()->id,
            'athlete_id' => $athlete->id,
        ]);

        return redirect()->route('messaging', ['thread' => $thread->id])->with('success', 'Conversation ouverte.');
    }

    public function storeMessage(StoreMessageRequest $request, MessageThread $thread): RedirectResponse
    {
        $this->authorize('sendMessage', $thread);

        $message = Message::create([
            'thread_id' => $thread->id,
            'sender_id' => $request->user()->id,
            'content' => $request->string('content')->toString(),
        ]);

        $thread->touch();

        MessageSent::dispatch($message);

        return redirect()->route('messaging', ['thread' => $thread->id])->with('success', 'Message envoyé.');
    }
}
