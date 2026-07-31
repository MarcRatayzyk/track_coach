<?php

namespace App\Support;

/**
 * Disk for coach/athlete private media (feedback videos, message audio).
 * Never use the public disk — files must not be reachable via /storage.
 */
class PrivateMediaDisk
{
    public const NAME = 'local';

    public static function name(): string
    {
        return self::NAME;
    }

    public static function isObjectStore(string $disk): bool
    {
        return $disk === 's3'
            || config("filesystems.disks.{$disk}.driver") === 's3';
    }
}
