<?php

namespace App\Http\Controllers\Web;

use App\Actions\PurgeOrphanSessionFeedbackUploadsAction;
use App\Http\Controllers\Controller;
use App\Models\SessionFeedback;
use App\Models\SessionFeedbackMedia;
use App\Support\VideoUploadDisk;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SessionFeedbackVideoUploadController extends Controller
{
    /**
     * @var list<string>
     */
    private const ALLOWED_MIME_TYPES = [
        'video/mp4',
        'video/webm',
        'video/quicktime',
        'video/x-msvideo',
        'video/3gpp',
        'video/3gpp2',
        'video/x-matroska',
        'video/x-m4v',
    ];

    public function store(
        Request $request,
        PurgeOrphanSessionFeedbackUploadsAction $purgeOrphans,
    ): JsonResponse {
        $this->authorize('create', SessionFeedback::class);

        if (! VideoUploadDisk::usesDirectUpload()) {
            throw ValidationException::withMessages([
                'video' => 'L’upload direct n’est pas configuré sur ce serveur.',
            ]);
        }

        $data = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'mime_type' => ['required', 'string', 'max:100'],
            'size_bytes' => ['required', 'integer', 'min:1', 'max:'.VideoUploadDisk::MAX_FILE_BYTES_S3],
        ]);

        if (! in_array($data['mime_type'], self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'mime_type' => 'Format vidéo non pris en charge (MP4, MOV, WebM, 3GP…).',
            ]);
        }

        $userId = (int) $request->user()->id;
        $purgeOrphans->forUser($userId, 30);

        $pendingCount = SessionFeedbackMedia::query()
            ->where('uploaded_by', $userId)
            ->whereIn('status', [
                SessionFeedbackMedia::STATUS_PENDING,
                SessionFeedbackMedia::STATUS_UPLOADED,
            ])
            ->whereNull('session_feedback_id')
            ->count();

        if ($pendingCount >= VideoUploadDisk::MAX_FILES) {
            // Tentative précédente abandonnée : libère les slots pour réessayer.
            $purgeOrphans->makeRoomForUser($userId, VideoUploadDisk::MAX_FILES);
        }

        $extension = pathinfo($data['filename'], PATHINFO_EXTENSION) ?: 'mp4';
        $extension = Str::lower(preg_replace('/[^a-z0-9]/i', '', $extension) ?: 'mp4');
        $path = 'session-feedbacks/pending/'.$userId.'/'.Str::uuid()->toString().'.'.$extension;

        $media = SessionFeedbackMedia::query()->create([
            'session_feedback_id' => null,
            'uploaded_by' => $userId,
            'kind' => SessionFeedbackMedia::KIND_VIDEO,
            'disk' => 's3',
            'path' => $path,
            'mime_type' => $data['mime_type'],
            'original_name' => $data['filename'],
            'size_bytes' => $data['size_bytes'],
            'sort_order' => 0,
            'status' => SessionFeedbackMedia::STATUS_PENDING,
        ]);

        $expiresAt = now()->addMinutes(30);
        $upload = Storage::disk('s3')->temporaryUploadUrl($path, $expiresAt, [
            'ContentType' => $data['mime_type'],
        ]);

        return response()->json([
            'id' => $media->id,
            'upload_url' => $upload['url'],
            'headers' => $upload['headers'] ?? [],
            'path' => $path,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function complete(Request $request, SessionFeedbackMedia $media): JsonResponse
    {
        $this->authorize('create', SessionFeedback::class);

        if ((int) $media->uploaded_by !== (int) $request->user()->id) {
            abort(403);
        }

        if ($media->kind !== SessionFeedbackMedia::KIND_VIDEO) {
            abort(404);
        }

        if ($media->status === SessionFeedbackMedia::STATUS_UPLOADED) {
            return response()->json(['id' => $media->id, 'status' => $media->status]);
        }

        if ($media->status !== SessionFeedbackMedia::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'video' => 'Cette vidéo ne peut plus être finalisée.',
            ]);
        }

        if (! Storage::disk($media->disk)->exists($media->path)) {
            $media->update(['status' => SessionFeedbackMedia::STATUS_FAILED]);

            throw ValidationException::withMessages([
                'video' => 'Le fichier n’a pas été trouvé sur le stockage. Réessayez l’envoi.',
            ]);
        }

        $size = Storage::disk($media->disk)->size($media->path);
        $media->update([
            'status' => SessionFeedbackMedia::STATUS_UPLOADED,
            'size_bytes' => $size > 0 ? $size : $media->size_bytes,
        ]);

        return response()->json([
            'id' => $media->id,
            'status' => $media->status,
        ]);
    }
}
