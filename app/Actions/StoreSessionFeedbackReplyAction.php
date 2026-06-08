<?php

namespace App\Actions;

use App\Models\DashboardTask;
use App\Models\SessionFeedback;
use App\Models\SessionFeedbackReply;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreSessionFeedbackReplyAction
{
    public function __construct(
        private readonly StoreSessionFeedbackMediaAction $storeMedia,
    ) {}

    /**
     * @param  list<UploadedFile>  $audioFiles
     */
    public function execute(
        User $coach,
        SessionFeedback $feedback,
        ?string $body,
        array $audioFiles,
    ): SessionFeedbackReply {
        if ($feedback->reply()->exists()) {
            throw ValidationException::withMessages([
                'body' => 'Une réponse a déjà été envoyée pour ce retour.',
            ]);
        }

        return DB::transaction(function () use ($coach, $feedback, $body, $audioFiles): SessionFeedbackReply {
            $reply = SessionFeedbackReply::query()->create([
                'session_feedback_id' => $feedback->id,
                'coach_id' => $coach->id,
                'body' => $body,
            ]);

            if ($audioFiles !== []) {
                $this->storeMedia->storeAudioForReply($reply, $audioFiles);
            }

            $feedback->update(['status' => SessionFeedback::STATUS_COACH_REPLIED]);

            DashboardTask::query()
                ->where('session_feedback_id', $feedback->id)
                ->update([
                    'status' => 'done',
                    'completed_at' => now(),
                ]);

            return $reply->load('audioFiles');
        });
    }
}
