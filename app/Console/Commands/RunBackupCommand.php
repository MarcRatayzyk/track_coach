<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use ZipArchive;

class RunBackupCommand extends Command
{
    protected $signature = 'backup:run {--keep= : Number of recent backups to keep (defaults to config/backup.keep)}';

    protected $description = 'Create a compressed backup of the database (and public storage) and prune old ones';

    public function handle(): int
    {
        $destination = config('backup.destination', storage_path('app/backups'));
        File::ensureDirectoryExists($destination);

        $timestamp = now()->format('Y-m-d_His');
        $zipPath = rtrim($destination, '/\\').DIRECTORY_SEPARATOR."backup-{$timestamp}.zip";

        $dump = $this->dumpDatabase($timestamp);
        if ($dump === null) {
            return self::FAILURE;
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error("Impossible de créer l'archive {$zipPath}.");
            @unlink($dump['path']);

            return self::FAILURE;
        }

        $zip->addFile($dump['path'], $dump['name']);

        if (config('backup.include_public_storage', true)) {
            $this->addPublicStorage($zip);
        }

        $zip->close();
        @unlink($dump['path']);

        $this->info("Backup créé : {$zipPath}");

        $this->prune($destination);

        return self::SUCCESS;
    }

    /**
     * @return array{path: string, name: string}|null
     */
    private function dumpDatabase(string $timestamp): ?array
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $tmp = storage_path("app/backup-db-{$timestamp}");

        if ($connection === 'sqlite') {
            $database = $config['database'] ?? database_path('database.sqlite');
            if (! File::exists($database)) {
                $this->error("Base SQLite introuvable : {$database}");

                return null;
            }
            $path = $tmp.'.sqlite';
            File::copy($database, $path);

            return ['path' => $path, 'name' => 'database.sqlite'];
        }

        if ($connection === 'pgsql') {
            $path = $tmp.'.sql';
            $process = new Process([
                'pg_dump',
                '--host='.($config['host'] ?? '127.0.0.1'),
                '--port='.($config['port'] ?? '5432'),
                '--username='.($config['username'] ?? 'forge'),
                '--no-owner',
                '--no-privileges',
                '--file='.$path,
                $config['database'] ?? 'forge',
            ], base_path(), ['PGPASSWORD' => $config['password'] ?? '']);
            $process->setTimeout(600);
            $process->run();

            if (! $process->isSuccessful()) {
                $this->error('Échec de pg_dump : '.trim($process->getErrorOutput()));

                return null;
            }

            return ['path' => $path, 'name' => 'database.sql'];
        }

        $this->error("Connexion DB non supportée pour le backup : {$connection}");

        return null;
    }

    private function addPublicStorage(ZipArchive $zip): void
    {
        $root = storage_path('app/public');
        if (! File::isDirectory($root)) {
            return;
        }

        foreach (File::allFiles($root) as $file) {
            $zip->addFile($file->getRealPath(), 'storage/'.$file->getRelativePathname());
        }
    }

    private function prune(string $destination): void
    {
        $keep = (int) ($this->option('keep') ?? config('backup.keep', 7));
        if ($keep <= 0) {
            return;
        }

        $backups = collect(File::glob(rtrim($destination, '/\\').DIRECTORY_SEPARATOR.'backup-*.zip'))
            ->sortByDesc(fn (string $path): string => $path)
            ->values();

        $backups->slice($keep)->each(function (string $path): void {
            File::delete($path);
            $this->line('Ancien backup supprimé : '.basename($path));
        });
    }
}
