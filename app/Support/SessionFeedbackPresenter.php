<?php

namespace App\Support;

use App\Models\Message;
use App\Models\MessageMedia;
use App\Models\MessageThread;
use App\Models\ProgramTrainingDay;
use App\Models\SessionFeedback;
use App\Models\SessionFeedbackAnnotation;
use App\Models\SessionFeedbackMedia;
use Illuminate\Support\Collection;

class SessionFeedbackPresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function feedback(SessionFeedback $feedback): array
    {
        $feedback->loadMissing([
            'athlete:id,name',
            'programTrainingDay',
            'athleteVideos.annotations',
        ]);

        $replyMessage = $feedback->replyMessages()
            ->with(['audioFiles', 'sender:id,name'])
            ->latest()
            ->first();

        $day = $feedback->programTrainingDay;

        return [
            'id' => $feedback->id,
            'athlete_id' => $feedback->athlete_id,
            'athlete_name' => $feedback->athlete?->name,
            'session_date' => $feedback->session_date?->toDateString(),
            'session_label' => self::sessionLabel($day),
            'athlete_notes' => $feedback->athlete_notes,
            'status' => $feedback->status,
            'submitted_at' => $feedback->submitted_at?->toIso8601String(),
            'videos' => $feedback->athleteVideos->map(fn (SessionFeedbackMedia $m) => self::media($m))->values()->all(),
            'reply' => $replyMessage ? self::replyFromMessage($replyMessage) : null,
            'coach_thread_id' => MessageThread::query()
                ->where('coach_id', $feedback->coach_id)
                ->where('athlete_id', $feedback->athlete_id)
                ->value('id'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function replyFromMessage(Message $message): array
    {
        $message->loadMissing('audioFiles');

        return [
            'id' => $message->id,
            'body' => self::messageReplyBody($message),
            'created_at' => $message->created_at?->toIso8601String(),
            'audio_files' => $message->audioFiles
                ->map(fn (MessageMedia $m) => MessagePresenter::media($m))
                ->values()
                ->all(),
        ];
    }

    public static function messageReplyBody(Message $message): ?string
    {
        $content = trim($message->content ?? '');

        if ($content === '') {
            return null;
        }

        if (str_starts_with($content, 'Réponse à ton retour du ')) {
            $parts = explode("\n\n", $content, 2);

            return isset($parts[1]) ? trim($parts[1]) : null;
        }

        return $content;
    }

    /**
     * @return array<string, mixed>
     */
    public static function media(SessionFeedbackMedia $media): array
    {
        $media->loadMissing('annotations');

        return [
            'id' => $media->id,
            'kind' => $media->kind,
            'url' => $media->url(),
            'original_name' => $media->original_name,
            'mime_type' => $media->mime_type,
            'annotations' => $media->annotations
                ->map(fn (SessionFeedbackAnnotation $annotation) => self::annotation($annotation))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function annotation(SessionFeedbackAnnotation $annotation): array
    {
        return [
            'id' => $annotation->id,
            'timestamp_ms' => $annotation->timestamp_ms,
            'body' => $annotation->body,
            'shapes' => $annotation->shapes ?? [],
            'created_at' => $annotation->created_at?->toIso8601String(),
        ];
    }

    public static function sessionLabel(?ProgramTrainingDay $day): string
    {
        if ($day === null) {
            return 'Séance';
        }

        $parts = array_filter([
            $day->session_label,
            $day->main_lift,
        ]);

        return $parts !== [] ? implode(' · ', $parts) : 'Séance';
    }

    /**
     * @param  Collection<int, SessionFeedback>  $feedbacks
     * @return list<array<string, mixed>>
     */
    public static function list(Collection $feedbacks): array
    {
        return $feedbacks->map(function (SessionFeedback $feedback): array {
            $feedback->loadMissing(['athlete:id,name', 'programTrainingDay', 'athleteVideos']);

            return [
                'id' => $feedback->id,
                'athlete_id' => $feedback->athlete_id,
                'athlete_name' => $feedback->athlete?->name,
                'session_date' => $feedback->session_date?->toDateString(),
                'session_label' => self::sessionLabel($feedback->programTrainingDay),
                'athlete_notes' => $feedback->athlete_notes,
                'status' => $feedback->status,
                'submitted_at' => $feedback->submitted_at?->toIso8601String(),
                'video_count' => $feedback->athleteVideos->count(),
                'has_reply' => $feedback->status === SessionFeedback::STATUS_COACH_REPLIED,
            ];
        })->values()->all();
    }
}
