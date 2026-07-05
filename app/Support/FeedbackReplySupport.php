<?php

namespace App\Support;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\SessionFeedback;

class FeedbackReplySupport
{
    public static function createCoachReply(SessionFeedback $feedback, string $body): Message
    {
        $thread = MessageThread::query()->firstOrCreate([
            'coach_id' => $feedback->coach_id,
            'athlete_id' => $feedback->athlete_id,
        ]);

        return Message::query()->create([
            'thread_id' => $thread->id,
            'sender_id' => $feedback->coach_id,
            'session_feedback_id' => $feedback->id,
            'content' => $body,
        ]);
    }
}
