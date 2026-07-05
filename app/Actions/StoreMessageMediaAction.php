<?php

namespace App\Actions;

use App\Models\Message;
use App\Models\MessageMedia;
use App\Models\MessageThread;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class StoreMessageMediaAction
{
    /**
     * @param  list<UploadedFile>  $files
     * @return list<MessageMedia>
     */
    public function storeAudio(Message $message, array $files): array
    {
        $disk = 'public';
        $stored = [];

        foreach (array_values($files) as $index => $file) {
            $extension = $file->getClientOriginalExtension() ?: 'bin';
            $filename = Str::uuid()->toString().'.'.$extension;
            $path = $file->storeAs(
                "messages/{$message->id}/audio",
                $filename,
                $disk,
            );

            $stored[] = MessageMedia::query()->create([
                'message_id' => $message->id,
                'kind' => MessageMedia::KIND_AUDIO,
                'disk' => $disk,
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'original_name' => $file->getClientOriginalName(),
                'size_bytes' => $file->getSize(),
                'sort_order' => $index,
            ]);
        }

        return $stored;
    }
}
