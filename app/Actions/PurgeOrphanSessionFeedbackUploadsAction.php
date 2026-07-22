<?php

namespace App\Actions;

use App\Models\SessionFeedbackMedia;
use Illuminate\Support\Facades\Storage;

class PurgeOrphanSessionFeedbackUploadsAction
{
    /**
     * Remove stale orphan uploads for a user so they can start a new send.
     *
     * @return int Number of deleted rows
     */
    public function forUser(int $userId, int $staleMinutes = 30): int
    {
        $cutoff = now()->subMinutes(max(1, $staleMinutes));

        $orphans = SessionFeedbackMedia::query()
            ->where('uploaded_by', $userId)
            ->whereNull('session_feedback_id')
            ->where('kind', SessionFeedbackMedia::KIND_VIDEO)
            ->where(function ($query) use ($cutoff): void {
                $query
                    ->where('status', SessionFeedbackMedia::STATUS_FAILED)
                    ->orWhere(function ($q) use ($cutoff): void {
                        $q->whereIn('status', [
                            SessionFeedbackMedia::STATUS_PENDING,
                            SessionFeedbackMedia::STATUS_UPLOADED,
                        ])->where('created_at', '<', $cutoff);
                    });
            })
            ->get();

        return $this->deleteMediaCollection($orphans);
    }

    /**
     * If the user is still at the pending/uploaded cap, drop the oldest orphans
     * so a new upload session can start (previous attempt was abandoned).
     *
     * @return int Number of deleted rows
     */
    public function makeRoomForUser(int $userId, int $maxFiles): int
    {
        $orphans = SessionFeedbackMedia::query()
            ->where('uploaded_by', $userId)
            ->whereNull('session_feedback_id')
            ->where('kind', SessionFeedbackMedia::KIND_VIDEO)
            ->whereIn('status', [
                SessionFeedbackMedia::STATUS_PENDING,
                SessionFeedbackMedia::STATUS_UPLOADED,
                SessionFeedbackMedia::STATUS_FAILED,
            ])
            ->orderBy('created_at')
            ->get();

        if ($orphans->count() < $maxFiles) {
            return 0;
        }

        // Previous batch abandoned: clear all orphans so the athlete can retry.
        return $this->deleteMediaCollection($orphans);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SessionFeedbackMedia>  $mediaItems
     */
    private function deleteMediaCollection($mediaItems): int
    {
        $deleted = 0;

        foreach ($mediaItems as $media) {
            try {
                Storage::disk($media->disk)->delete($media->path);
            } catch (\Throwable) {
                // Continue even if the object is already gone.
            }

            $media->delete();
            $deleted++;
        }

        return $deleted;
    }
}
