<?php

namespace App\Console\Commands;

use App\Actions\OptimizeFeedbackVideoForPlaybackAction;
use App\Models\SessionFeedbackMedia;
use Illuminate\Console\Command;

class OptimizeFeedbackVideosCommand extends Command
{
    protected $signature = 'feedbacks:optimize-videos {--limit=50 : Nombre max de vidéos à traiter}';

    protected $description = 'Remux les vidéos de retours (faststart) pour une lecture progressive plus rapide';

    public function handle(OptimizeFeedbackVideoForPlaybackAction $optimize): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $videos = SessionFeedbackMedia::query()
            ->where('kind', SessionFeedbackMedia::KIND_VIDEO)
            ->whereIn('status', [
                SessionFeedbackMedia::STATUS_ATTACHED,
                SessionFeedbackMedia::STATUS_UPLOADED,
            ])
            ->where('path', 'not like', '%-fast.mp4')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($videos->isEmpty()) {
            $this->info('Aucune vidéo à optimiser.');

            return self::SUCCESS;
        }

        $ok = 0;
        foreach ($videos as $media) {
            $this->line("Media #{$media->id}…");
            if ($optimize->execute($media)) {
                $ok++;
                $this->info('  OK');
            } else {
                $this->warn('  ignoré / échec');
            }
        }

        $this->info("Optimisées : {$ok}/{$videos->count()}");

        return self::SUCCESS;
    }
}
