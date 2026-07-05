<?php

namespace App\Http\Controllers\Web\Coach;

use App\Actions\SendMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMessageRequest;
use App\Http\Requests\StoreThreadRequest;
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

    public function storeMessage(
        StoreMessageRequest $request,
        MessageThread $thread,
        SendMessageAction $action,
    ): RedirectResponse {
        $this->authorize('sendMessage', $thread);

        $action->execute(
            $request->user(),
            $thread,
            $request->validated('content'),
            $request->file('audio_files', []),
            $request->validated('session_feedback_id'),
        );

        $params = ['thread' => $thread->id];
        if ($request->filled('session_feedback_id')) {
            unset($params['feedback']);
        }

        return redirect()
            ->route('messaging', $params)
            ->with('success', 'Message envoyé.');
    }
}
