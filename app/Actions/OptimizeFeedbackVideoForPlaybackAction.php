<?php

namespace App\Actions;

use App\Models\SessionFeedbackMedia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Remux MP4 avec moov en tête (faststart) pour permettre la lecture progressive
 * sans attendre le téléchargement complet du fichier.
 */
class OptimizeFeedbackVideoForPlaybackAction
{
    public function execute(SessionFeedbackMedia $media): bool
    {
        if ($media->kind !== SessionFeedbackMedia::KIND_VIDEO) {
            return false;
        }

        if (! $this->ffmpegAvailable()) {
            return false;
        }

        $disk = Storage::disk($media->disk);
        if (! $disk->exists($media->path)) {
            return false;
        }

        $extension = Str::lower(pathinfo($media->path, PATHINFO_EXTENSION) ?: 'mp4');
        if (! in_array($extension, ['mp4', 'm4v', 'mov'], true)) {
            return false;
        }

        $tempDir = storage_path('app/tmp/video-optimize');
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $token = Str::uuid()->toString();
        $inputPath = $tempDir.DIRECTORY_SEPARATOR."{$token}-in.{$extension}";
        $outputPath = $tempDir.DIRECTORY_SEPARATOR."{$token}-out.mp4";

        try {
            file_put_contents($inputPath, $disk->get($media->path));

            $result = Process::timeout(180)->run([
                'ffmpeg',
                '-y',
                '-i', $inputPath,
                '-c', 'copy',
                '-movflags', '+faststart',
                $outputPath,
            ]);

            if (! $result->successful() || ! is_file($outputPath) || filesize($outputPath) < 1024) {
                Log::warning('feedback video faststart failed', [
                    'media_id' => $media->id,
                    'stderr' => $result->errorOutput(),
                ]);

                return false;
            }

            $optimizedBytes = filesize($outputPath);
            $newPath = preg_replace('/\.[^.]+$/', '', $media->path).'-fast.mp4';
            $disk->put($newPath, file_get_contents($outputPath), [
                'visibility' => 'private',
                'ContentType' => 'video/mp4',
            ]);

            $oldPath = $media->path;
            $media->update([
                'path' => $newPath,
                'mime_type' => 'video/mp4',
                'size_bytes' => $optimizedBytes > 0 ? $optimizedBytes : $media->size_bytes,
            ]);

            if ($oldPath !== $newPath) {
                $disk->delete($oldPath);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('feedback video faststart exception', [
                'media_id' => $media->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        } finally {
            @unlink($inputPath);
            @unlink($outputPath);
        }
    }

    private function ffmpegAvailable(): bool
    {
        try {
            $result = Process::timeout(5)->run(['ffmpeg', '-version']);

            return $result->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
