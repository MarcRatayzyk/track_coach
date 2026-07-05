<?php

namespace App\Actions;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Support\MessagingInboxSupport;
use Illuminate\Support\Facades\DB;

class SendMessageAction
{
    public function __construct(
        private readonly StoreMessageMediaAction $storeMedia,
        private readonly SendFeedbackReplyMessageAction $feedbackReply,
    ) {}

    /**
     * @param  list<\Illuminate\Http\UploadedFile>  $audioFiles
     */
    public function execute(
        User $sender,
        MessageThread $thread,
        ?string $content,
        array $audioFiles = [],
        ?int $sessionFeedbackId = null,
    ): Message {
        if ($sessionFeedbackId !== null) {
            return $this->feedbackReply->execute(
                $sender,
                $thread,
                $sessionFeedbackId,
                $content,
                $audioFiles,
            );
        }

        return DB::transaction(function () use ($sender, $thread, $content, $audioFiles): Message {
            $thread->loadMissing(['coach', 'athlete']);

            $message = Message::query()->create([
                'thread_id' => $thread->id,
                'sender_id' => $sender->id,
                'content' => $content ?? '',
            ]);

            if ($audioFiles !== []) {
                $this->storeMedia->storeAudio($message, $audioFiles);
            }

            $thread->touch();

            $message->load(['sender:id,name', 'audioFiles']);
            MessageSent::dispatch($message);
            MessagingInboxSupport::dispatchThreadUpdated($thread);

            $recipient = $thread->coach_id === $sender->id
                ? $thread->athlete
                : $thread->coach;

            $recipient?->notify(new NewMessageNotification($message));

            return $message;
        });
    }
}
