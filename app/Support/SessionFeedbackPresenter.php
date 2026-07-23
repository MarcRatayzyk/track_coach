<?php

namespace App\Support;

use App\Models\Message;
use App\Models\MessageMedia;
use App\Models\MessageThread;
use App\Models\ProgramDayExercise;
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
            'series' => self::seriesFromSnapshot($media->series_info),
            'annotations' => $media->annotations
                ->map(fn (SessionFeedbackAnnotation $annotation) => self::annotation($annotation))
                ->values()
                ->all(),
        ];
    }

    /**
     * Option de série présentée à l'athlète pour rattacher une vidéo à un exercice planifié.
     *
     * @return array<string, mixed>
     */
    public static function seriesOption(ProgramDayExercise $exercise): array
    {
        return [
            'id' => $exercise->id,
            'label' => self::seriesLabel($exercise),
            'section' => $exercise->section,
            'section_label' => self::sectionLabel($exercise->section),
            'exercise_name' => self::exerciseName($exercise),
            'sets' => $exercise->sets,
            'reps' => $exercise->reps,
            'load' => $exercise->load,
            'load_percent' => $exercise->load_percent,
            'rpe' => $exercise->rpe,
            'summary' => self::seriesSummary($exercise),
        ];
    }

    /**
     * Snapshot figé stocké sur la vidéo au moment de l'envoi.
     *
     * @return array<string, mixed>
     */
    public static function seriesSnapshot(ProgramDayExercise $exercise): array
    {
        return self::seriesOption($exercise);
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @return array<string, mixed>|null
     */
    private static function seriesFromSnapshot(?array $snapshot): ?array
    {
        if ($snapshot === null || $snapshot === []) {
            return null;
        }

        return $snapshot;
    }

    private static function exerciseName(ProgramDayExercise $exercise): string
    {
        $name = trim((string) $exercise->exercise_name);
        if ($name !== '') {
            return $name;
        }

        $lift = trim((string) $exercise->lift);

        return $lift !== '' ? ucfirst($lift) : 'Exercice';
    }

    private static function seriesLabel(ProgramDayExercise $exercise): string
    {
        $name = self::exerciseName($exercise);
        $section = self::sectionLabel($exercise->section);

        return $section !== '' ? "{$name} — {$section}" : $name;
    }

    public static function sectionLabel(?string $section): string
    {
        return match ($section) {
            ProgramDayExercise::SECTION_TOPSET => 'Top set',
            ProgramDayExercise::SECTION_BACKOFF => 'Back-off',
            ProgramDayExercise::SECTION_ACCESSORY => 'Accessoire',
            ProgramDayExercise::SECTION_WARMUP => 'Échauffement',
            default => '',
        };
    }

    private static function seriesSummary(ProgramDayExercise $exercise): string
    {
        $parts = [];

        $sets = trim((string) $exercise->sets);
        $reps = trim((string) $exercise->reps);
        if ($sets !== '' && $reps !== '') {
            $parts[] = "{$sets} × {$reps}";
        } elseif ($reps !== '') {
            $parts[] = "{$reps} reps";
        } elseif ($sets !== '') {
            $parts[] = "{$sets} séries";
        }

        if ($exercise->load !== null) {
            $parts[] = self::formatNumber((float) $exercise->load).' kg';
        } elseif ($exercise->load_percent !== null) {
            $parts[] = self::formatNumber((float) $exercise->load_percent).' %';
        }

        if ($exercise->rpe !== null) {
            $parts[] = 'RPE '.self::formatNumber((float) $exercise->rpe);
        }

        return implode(' · ', $parts);
    }

    private static function formatNumber(float $value): string
    {
        if (floor($value) === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
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
