<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MessageMedia;
use App\Models\SessionFeedbackMedia;
use App\Support\PrivateMediaDisk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateMediaController extends Controller
{
    public function showSessionFeedbackMedia(Request $request, SessionFeedbackMedia $media): StreamedResponse|RedirectResponse
    {
        $user = $request->user();

        if ($media->session_feedback_id !== null) {
            $feedback = $media->feedback;
            abort_unless($feedback !== null, 404);
            $this->authorize('view', $feedback);
        } else {
            abort_unless($media->uploaded_by === $user->id, 403);
        }

        return $this->stream($media->disk, $media->path, $media->original_name, $media->mime_type);
    }

    public function showMessageMedia(Request $request, MessageMedia $media): StreamedResponse|RedirectResponse
    {
        $message = $media->message()->with('thread')->firstOrFail();
        $this->authorize('view', $message->thread);

        return $this->stream($media->disk, $media->path, $media->original_name, $media->mime_type);
    }

    private function stream(
        string $diskName,
        string $path,
        ?string $originalName,
        ?string $mimeType,
    ): StreamedResponse|RedirectResponse {
        $disk = Storage::disk($diskName);

        if (! $disk->exists($path)) {
            abort(404);
        }

        if (PrivateMediaDisk::isObjectStore($diskName)) {
            return redirect()->away(
                $disk->temporaryUrl($path, now()->addHour()),
            );
        }

        return $disk->response(
            $path,
            $originalName ?: basename($path),
            [
                'Content-Type' => $mimeType ?: 'application/octet-stream',
                'Cache-Control' => 'private, max-age=3600',
            ],
            'inline',
        );
    }
}
