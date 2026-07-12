<?php

namespace App\Actions;

use App\Events\MessageSent;
use App\Models\DashboardTask;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\SessionFeedback;
use App\Models\User;
use App\Notifications\FeedbackRepliedNotification;
use App\Support\MailSendSupport;
use App\Support\MessagingInboxSupport;
use App\Support\SessionFeedbackPresenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SendFeedbackReplyMessageAction
{
    public function __construct(
        private readonly StoreMessageMediaAction $storeMedia,
    ) {}

    /**
     * @param  list<\Illuminate\Http\UploadedFile>  $audioFiles
     */
    public function execute(
        User $sender,
        MessageThread $thread,
        int $sessionFeedbackId,
        ?string $content,
        array $audioFiles,
    ): Message {
        $feedback = SessionFeedback::query()->findOrFail($sessionFeedbackId);

        if (! $sender->can('reply', $feedback)) {
            throw ValidationException::withMessages([
                'session_feedback_id' => 'Vous ne pouvez pas répondre à ce retour.',
            ]);
        }

        if ($thread->coach_id !== $feedback->coach_id || $thread->athlete_id !== $feedback->athlete_id) {
            throw ValidationException::withMessages([
                'session_feedback_id' => 'Ce retour ne correspond pas à cette conversation.',
            ]);
        }

        $body = trim((string) $content);
        if ($body === '' && $audioFiles === []) {
            throw ValidationException::withMessages([
                'content' => 'Ajoutez un message texte ou au moins un fichier audio.',
            ]);
        }

        return DB::transaction(function () use ($sender, $thread, $feedback, $body, $audioFiles): Message {
            $feedback->loadMissing('programTrainingDay');
            $sessionLabel = SessionFeedbackPresenter::sessionLabel($feedback->programTrainingDay);
            $sessionDate = $feedback->session_date?->locale('fr')->isoFormat('D MMMM YYYY') ?? '';

            $prefix = "Réponse à ton retour du {$sessionDate} — {$sessionLabel}";
            $messageContent = $body !== '' ? "{$prefix}\n\n{$body}" : $prefix;

            $message = Message::query()->create([
                'thread_id' => $thread->id,
                'sender_id' => $sender->id,
                'session_feedback_id' => $feedback->id,
                'content' => $messageContent,
            ]);

            if ($audioFiles !== []) {
                $this->storeMedia->storeAudio($message, $audioFiles);
            }

            $feedback->update(['status' => SessionFeedback::STATUS_COACH_REPLIED]);

            DashboardTask::query()
                ->where('session_feedback_id', $feedback->id)
                ->update([
                    'status' => 'done',
                    'completed_at' => now(),
                ]);

            $thread->touch();

            $message->load(['sender:id,name', 'audioFiles', 'sessionFeedback.programTrainingDay']);
            MessageSent::dispatch($message);
            MessagingInboxSupport::dispatchThreadUpdated($thread);

            MailSendSupport::notifySafely($feedback->athlete, new FeedbackRepliedNotification($message));

            return $message;
        });
    }
}
