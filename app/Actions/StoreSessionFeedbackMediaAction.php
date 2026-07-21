<?php

namespace App\Actions;

use App\Models\SessionFeedback;
use App\Models\SessionFeedbackMedia;
use App\Models\User;
use App\Support\VideoUploadDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreSessionFeedbackMediaAction
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<SessionFeedbackMedia>
     */
    public function storeVideos(SessionFeedback $feedback, array $files, ?int $uploadedBy = null): array
    {
        return $this->storeFiles(
            $feedback,
            $files,
            SessionFeedbackMedia::KIND_VIDEO,
            'videos',
            $uploadedBy ?? $feedback->athlete_id,
        );
    }

    /**
     * @param  list<int>  $mediaIds
     * @return list<SessionFeedbackMedia>
     */
    public function attachUploadedVideos(SessionFeedback $feedback, User $athlete, array $mediaIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $mediaIds)));

        if (count($ids) > VideoUploadDisk::MAX_FILES) {
            throw ValidationException::withMessages([
                'video_upload_ids' => 'Vous pouvez envoyer au maximum '.VideoUploadDisk::MAX_FILES.' vidéos.',
            ]);
        }

        $mediaItems = SessionFeedbackMedia::query()
            ->whereIn('id', $ids)
            ->where('uploaded_by', $athlete->id)
            ->where('kind', SessionFeedbackMedia::KIND_VIDEO)
            ->where('status', SessionFeedbackMedia::STATUS_UPLOADED)
            ->whereNull('session_feedback_id')
            ->get()
            ->keyBy('id');

        if ($mediaItems->count() !== count($ids)) {
            throw ValidationException::withMessages([
                'video_upload_ids' => 'Une ou plusieurs vidéos sont invalides ou non finalisées.',
            ]);
        }

        $attached = [];
        foreach ($ids as $index => $id) {
            /** @var SessionFeedbackMedia $media */
            $media = $mediaItems->get($id);
            $media->update([
                'session_feedback_id' => $feedback->id,
                'status' => SessionFeedbackMedia::STATUS_ATTACHED,
                'sort_order' => $index,
            ]);
            $attached[] = $media->fresh();
        }

        return $attached;
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<SessionFeedbackMedia>
     */
    private function storeFiles(
        SessionFeedback $feedback,
        array $files,
        string $kind,
        string $subdir,
        int $uploadedBy,
    ): array {
        $disk = 'public';
        $stored = [];

        foreach (array_values($files) as $index => $file) {
            $extension = $file->getClientOriginalExtension() ?: 'bin';
            $filename = Str::uuid()->toString().'.'.$extension;
            $path = $file->storeAs(
                "session-feedbacks/{$feedback->id}/{$subdir}",
                $filename,
                $disk,
            );

            $stored[] = SessionFeedbackMedia::query()->create([
                'session_feedback_id' => $feedback->id,
                'uploaded_by' => $uploadedBy,
                'kind' => $kind,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
                'sort_order' => $index,
                'status' => SessionFeedbackMedia::STATUS_ATTACHED,
            ]);
        }

        return $stored;
    }

    public function deleteMedia(SessionFeedbackMedia $media): void
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }
}
