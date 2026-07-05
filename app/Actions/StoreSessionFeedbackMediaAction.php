<?php

namespace App\Actions;

use App\Models\SessionFeedback;
use App\Models\SessionFeedbackMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreSessionFeedbackMediaAction
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<SessionFeedbackMedia>
     */
    public function storeVideos(SessionFeedback $feedback, array $files): array
    {
        return $this->storeFiles(
            $feedback,
            $files,
            SessionFeedbackMedia::KIND_VIDEO,
            'videos',
        );
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
                'kind' => $kind,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
                'sort_order' => $index,
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
