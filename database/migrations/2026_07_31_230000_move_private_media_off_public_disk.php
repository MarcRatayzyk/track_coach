<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        $this->movePublicMediaToLocal('session_feedback_media');
        $this->movePublicMediaToLocal('message_media');
    }

    public function down(): void
    {
        // Intentionally irreversible: files stay on the private local disk.
    }

    private function movePublicMediaToLocal(string $table): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $public = Storage::disk('public');
        $local = Storage::disk('local');

        DB::table($table)
            ->where('disk', 'public')
            ->orderBy('id')
            ->chunkById(50, function ($rows) use ($table, $public, $local): void {
                foreach ($rows as $row) {
                    $path = (string) $row->path;

                    if ($path !== '' && $public->exists($path)) {
                        if (! $local->exists($path)) {
                            $stream = $public->readStream($path);
                            if ($stream !== false) {
                                $local->writeStream($path, $stream);
                                if (is_resource($stream)) {
                                    fclose($stream);
                                }
                            }
                        }

                        $public->delete($path);
                    }

                    DB::table($table)->where('id', $row->id)->update(['disk' => 'local']);
                }
            });
    }
};
