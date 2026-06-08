<?php

namespace App\Support;

use App\Models\ProgramTrainingDay;
use App\Models\SessionFeedback;
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
            'athleteVideos',
            'reply.audioFiles',
        ]);

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
            'reply' => $feedback->reply ? self::reply($feedback) : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function reply(SessionFeedback $feedback): array
    {
        $reply = $feedback->reply;
        if ($reply === null) {
            return [];
        }

        $reply->loadMissing('audioFiles');

        return [
            'id' => $reply->id,
            'body' => $reply->body,
            'created_at' => $reply->created_at?->toIso8601String(),
            'audio_files' => $reply->audioFiles->map(fn (SessionFeedbackMedia $m) => self::media($m))->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function media(SessionFeedbackMedia $media): array
    {
        return [
            'id' => $media->id,
            'kind' => $media->kind,
            'url' => $media->url(),
            'original_name' => $media->original_name,
            'mime_type' => $media->mime_type,
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
