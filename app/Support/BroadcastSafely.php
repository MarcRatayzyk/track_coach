<?php

namespace App\Support;

use Illuminate\Broadcasting\BroadcastException;
use Illuminate\Support\Facades\Log;
use Throwable;

class BroadcastSafely
{
    /**
     * Dispatch realtime events without failing the HTTP request when Reverb/Pusher is down.
     */
    public static function run(callable $callback): void
    {
        try {
            $callback();
        } catch (BroadcastException $exception) {
            Log::warning('Broadcast unavailable: '.$exception->getMessage());
        } catch (Throwable $exception) {
            $message = $exception->getMessage();

            if (
                str_contains($message, 'Pusher error')
                || str_contains($message, 'Failed to connect')
                || str_contains($message, 'cURL error 7')
            ) {
                Log::warning('Broadcast unavailable: '.$message);

                return;
            }

            throw $exception;
        }
    }
}
