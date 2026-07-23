<?php

namespace App\Actions;

use App\Models\Message;
use App\Models\MessageMedia;
use App\Models\MessageThread;
use App\Models\SessionFeedbackMedia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class DeleteUserAccountAction
{
    /**
     * Permanently delete a user account and all associated data.
     *
     * Database rows are removed through the cascadeOnDelete foreign keys when
     * the user is deleted; this action additionally removes the physical media
     * files (local disk or S3/R2) that cascades cannot clean up.
     */
    public function execute(User $user): void
    {
        $this->deleteFeedbackMediaFiles($user);
        $this->deleteMessageMediaFiles($user);
        $this->deleteCoachAvatar($user);

        DB::transaction(function () use ($user): void {
            if (Schema::hasTable('personal_access_tokens')) {
                $user->tokens()->delete();
            }

            $user->delete();
        });
    }

    private function deleteFeedbackMediaFiles(User $user): void
    {
        SessionFeedbackMedia::query()
            ->whereHas('feedback', function ($query) use ($user): void {
                $query->where('athlete_id', $user->id)
                    ->orWhere('coach_id', $user->id);
            })
            ->each(function (SessionFeedbackMedia $media): void {
                $this->safeDelete($media->disk, $media->path);
            });
    }

    private function deleteMessageMediaFiles(User $user): void
    {
        $threadIds = MessageThread::query()
            ->where('coach_id', $user->id)
            ->orWhere('athlete_id', $user->id)
            ->pluck('id');

        if ($threadIds->isEmpty()) {
            return;
        }

        $messageIds = Message::query()->whereIn('thread_id', $threadIds)->pluck('id');

        MessageMedia::query()
            ->whereIn('message_id', $messageIds)
            ->each(function (MessageMedia $media): void {
                $this->safeDelete($media->disk, $media->path);
            });
    }

    private function deleteCoachAvatar(User $user): void
    {
        $avatar = $user->coachProfile?->avatar_path;
        if ($avatar) {
            $this->safeDelete('public', $avatar);
        }
    }

    private function safeDelete(?string $disk, ?string $path): void
    {
        if (! $disk || ! $path) {
            return;
        }

        try {
            Storage::disk($disk)->delete($path);
        } catch (Throwable) {
            // Ignore missing files / unreachable disks so account deletion never blocks.
        }
    }
}
