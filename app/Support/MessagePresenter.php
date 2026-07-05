<?php

namespace App\Support;

use App\Models\Message;
use App\Models\MessageMedia;

class MessagePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function message(Message $message): array
    {
        $message->loadMissing([
            'sender:id,name',
            'audioFiles',
            'sessionFeedback.programTrainingDay',
        ]);

        $feedback = $message->sessionFeedback;

        return [
            'id' => $message->id,
            'thread_id' => $message->thread_id,
            'sender_id' => $message->sender_id,
            'content' => $message->content,
            'created_at' => $message->created_at?->toIso8601String(),
            'sender' => $message->sender ? [
                'id' => $message->sender->id,
                'name' => $message->sender->name,
            ] : null,
            'audio_files' => $message->audioFiles
                ->map(fn (MessageMedia $media) => self::media($media))
                ->values()
                ->all(),
            'session_feedback' => $feedback ? [
                'id' => $feedback->id,
                'session_date' => $feedback->session_date?->toDateString(),
                'session_label' => SessionFeedbackPresenter::sessionLabel($feedback->programTrainingDay),
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function media(MessageMedia $media): array
    {
        return [
            'id' => $media->id,
            'kind' => $media->kind,
            'url' => $media->url(),
            'original_name' => $media->original_name,
            'mime_type' => $media->mime_type,
        ];
    }

    /**
     * @param  iterable<int, Message>  $messages
     * @return list<array<string, mixed>>
     */
    public static function list(iterable $messages): array
    {
        $items = [];

        foreach ($messages as $message) {
            $items[] = self::message($message);
        }

        return $items;
    }
}
