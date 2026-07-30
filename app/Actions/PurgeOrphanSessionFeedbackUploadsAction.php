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
     * Libère au moins un slot orphelin (pending/uploaded/failed) pour un nouvel upload.
     * Ne supprime que les plus anciens — jamais toute la batch en cours.
     *
     * @return int Number of deleted rows
     */
    public function makeRoomForUser(int $userId, int $maxFiles): int
    {
        $maxFiles = max(1, $maxFiles);

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
            ->orderBy('id')
            ->get();

        // Besoin d'1 place pour le prochain fichier : ne retirer que le surplus.
        $toRemove = $orphans->count() - $maxFiles + 1;
        if ($toRemove <= 0) {
            return 0;
        }

        return $this->deleteMediaCollection($orphans->take($toRemove));
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
