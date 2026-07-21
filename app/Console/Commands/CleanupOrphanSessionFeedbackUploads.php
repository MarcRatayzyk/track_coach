<?php

namespace App\Console\Commands;

use App\Models\SessionFeedbackMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanSessionFeedbackUploads extends Command
{
    protected $signature = 'feedbacks:cleanup-orphan-uploads {--hours=24 : Age threshold in hours}';

    protected $description = 'Delete pending/uploaded session feedback videos not attached to a feedback';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $cutoff = now()->subHours($hours);

        $orphans = SessionFeedbackMedia::query()
            ->whereNull('session_feedback_id')
            ->whereIn('status', [
                SessionFeedbackMedia::STATUS_PENDING,
                SessionFeedbackMedia::STATUS_UPLOADED,
                SessionFeedbackMedia::STATUS_FAILED,
            ])
            ->where('created_at', '<', $cutoff)
            ->get();

        $deleted = 0;

        foreach ($orphans as $media) {
            try {
                Storage::disk($media->disk)->delete($media->path);
            } catch (\Throwable) {
                // Continue deleting DB row even if object is already gone.
            }

            $media->delete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} orphan upload(s) older than {$hours}h.");

        return self::SUCCESS;
    }
}
