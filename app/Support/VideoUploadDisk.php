<?php

namespace App\Support;

class VideoUploadDisk
{
    public const MAX_FILES = 3;

    public const MAX_FILE_BYTES_S3 = 200 * 1024 * 1024;

    public static function usesDirectUpload(): bool
    {
        $key = (string) config('filesystems.disks.s3.key');
        $secret = (string) config('filesystems.disks.s3.secret');
        $bucket = (string) config('filesystems.disks.s3.bucket');

        return $key !== '' && $secret !== '' && $bucket !== '';
    }

    public static function diskName(): string
    {
        return self::usesDirectUpload() ? 's3' : 'public';
    }

    /**
     * @return array{maxFiles:int, maxFileBytes:int, driver:string}
     */
    public static function uploadLimits(int $localMaxFileBytes): array
    {
        if (self::usesDirectUpload()) {
            return [
                'maxFiles' => self::MAX_FILES,
                'maxFileBytes' => self::MAX_FILE_BYTES_S3,
                'driver' => 's3',
            ];
        }

        return [
            'maxFiles' => self::MAX_FILES,
            'maxFileBytes' => max(0, $localMaxFileBytes),
            'driver' => 'local',
        ];
    }
}
