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
use App\Models\TrainingSession;
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
            'programTrainingDay.exercises',
            'athleteVideos.annotations',
        ]);

        $replyMessage = $feedback->replyMessages()
            ->with(['audioFiles', 'sender:id,name'])
            ->latest()
            ->first();

        $day = $feedback->programTrainingDay;
        $loggedItems = self::loggedItemsForFeedback($feedback);

        return [
            'id' => $feedback->id,
            'athlete_id' => $feedback->athlete_id,
            'athlete_name' => $feedback->athlete?->name,
            'session_date' => $feedback->session_date?->toDateString(),
            'session_label' => self::sessionLabel($day),
            'athlete_notes' => $feedback->athlete_notes,
            'status' => $feedback->status,
            'submitted_at' => $feedback->submitted_at?->toIso8601String(),
            'session_exercises' => self::sessionExercisesComparison($day, $loggedItems),
            'videos' => $feedback->athleteVideos
                ->map(fn (SessionFeedbackMedia $m) => self::media($m, $loggedItems))
                ->values()
                ->all(),
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
     * Comparaison prévu / réalisé pour tous les exercices de la séance (hors échauffement).
     *
     * @param  list<array<string, mixed>>  $loggedItems
     * @return list<array<string, mixed>>
     */
    private static function sessionExercisesComparison(?ProgramTrainingDay $day, array $loggedItems): array
    {
        if ($day === null) {
            return [];
        }

        $day->loadMissing('exercises');

        return $day->exercises
            ->filter(static fn (ProgramDayExercise $exercise): bool => $exercise->section !== ProgramDayExercise::SECTION_WARMUP)
            ->map(fn (ProgramDayExercise $exercise) => self::seriesComparison(
                self::seriesSnapshot($exercise),
                $loggedItems,
            ))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $loggedItems
     * @return array<string, mixed>
     */
    public static function media(SessionFeedbackMedia $media, array $loggedItems = []): array
    {
        $media->loadMissing('annotations');

        return [
            'id' => $media->id,
            'kind' => $media->kind,
            'url' => $media->url(),
            'original_name' => $media->original_name,
            'mime_type' => $media->mime_type,
            'size_bytes' => $media->size_bytes,
            'series' => self::seriesComparison($media->series_info, $loggedItems),
            'annotations' => $media->annotations
                ->map(fn (SessionFeedbackAnnotation $annotation) => self::annotation($annotation))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function loggedItemsForFeedback(SessionFeedback $feedback): array
    {
        if ($feedback->athlete_id === null || $feedback->session_date === null) {
            return [];
        }

        $session = TrainingSession::query()
            ->where('athlete_id', $feedback->athlete_id)
            ->whereDate('session_date', $feedback->session_date->toDateString())
            ->first();

        if ($session === null || ! is_array($session->items)) {
            return [];
        }

        return array_values(array_filter(
            $session->items,
            static fn ($item): bool => is_array($item),
        ));
    }

    /**
     * @param  array<string, mixed>|null  $snapshot
     * @param  list<array<string, mixed>>  $loggedItems
     * @return array<string, mixed>|null
     */
    private static function seriesComparison(?array $snapshot, array $loggedItems): ?array
    {
        $planned = self::seriesFromSnapshot($snapshot);
        if ($planned === null) {
            return null;
        }

        $actualLine = self::findMatchingLoggedItem($planned, $loggedItems);
        $actual = $actualLine !== null ? self::metricsFromLine($actualLine) : null;

        return [
            'id' => $planned['id'] ?? null,
            'label' => $planned['label'] ?? null,
            'section' => $planned['section'] ?? null,
            'section_label' => $planned['section_label'] ?? null,
            'exercise_name' => $planned['exercise_name'] ?? null,
            'summary' => $planned['summary'] ?? null,
            'planned' => self::metricsFromLine($planned),
            'actual' => $actual,
            'matches' => self::metricMatches(self::metricsFromLine($planned), $actual),
            // Compat : anciennes clés au niveau racine = prévu
            'sets' => $planned['sets'] ?? null,
            'reps' => $planned['reps'] ?? null,
            'load' => $planned['load'] ?? null,
            'load_percent' => $planned['load_percent'] ?? null,
            'rpe' => $planned['rpe'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $planned
     * @param  list<array<string, mixed>>  $loggedItems
     * @return array<string, mixed>|null
     */
    private static function findMatchingLoggedItem(array $planned, array $loggedItems): ?array
    {
        $plannedName = strtolower(trim((string) ($planned['exercise_name'] ?? '')));
        $plannedSection = trim((string) ($planned['section'] ?? ''));

        if ($plannedName === '') {
            return null;
        }

        $best = null;
        $bestScore = -1;

        foreach ($loggedItems as $item) {
            $line = self::flattenLoggedItem($item);
            $name = strtolower(trim((string) ($line['exercise_name'] ?? '')));
            if ($name === '' || $name !== $plannedName) {
                continue;
            }

            $section = trim((string) ($line['section'] ?? ''));
            $sectionScore = ($plannedSection === '' || $section === '' || $section === $plannedSection) ? 2 : 0;
            if ($sectionScore === 0 && $plannedSection !== '') {
                continue;
            }

            $score = $sectionScore;
            if (TrainingLoadSupport::valuesMatch($planned['sets'] ?? null, $line['sets'] ?? null)) {
                $score++;
            }
            if (TrainingLoadSupport::valuesMatch($planned['reps'] ?? null, $line['reps'] ?? null)) {
                $score++;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $line;
            }
        }

        return $best;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    private static function flattenLoggedItem(array $item): array
    {
        if (! isset($item['line']) || ! is_array($item['line'])) {
            return $item;
        }

        return array_merge($item, $item['line']);
    }

    /**
     * @param  array<string, mixed>  $line
     * @return array{sets: mixed, reps: mixed, load: mixed, load_percent: mixed, rpe: mixed}
     */
    private static function metricsFromLine(array $line): array
    {
        return [
            'sets' => $line['sets'] ?? null,
            'reps' => $line['reps'] ?? null,
            'load' => $line['load'] ?? null,
            'load_percent' => $line['load_percent'] ?? null,
            'rpe' => $line['rpe'] ?? null,
        ];
    }

    /**
     * @param  array{sets: mixed, reps: mixed, load: mixed, load_percent: mixed, rpe: mixed}  $planned
     * @param  array{sets: mixed, reps: mixed, load: mixed, load_percent: mixed, rpe: mixed}|null  $actual
     * @return array{sets: bool|null, reps: bool|null, load: bool|null, rpe: bool|null}
     */
    private static function metricMatches(array $planned, ?array $actual): array
    {
        if ($actual === null) {
            return [
                'sets' => null,
                'reps' => null,
                'load' => null,
                'rpe' => null,
            ];
        }

        return [
            'sets' => self::compareMetric($planned['sets'] ?? null, $actual['sets'] ?? null),
            'reps' => self::compareMetric($planned['reps'] ?? null, $actual['reps'] ?? null),
            'load' => self::compareLoad($planned, $actual),
            'rpe' => self::compareMetric($planned['rpe'] ?? null, $actual['rpe'] ?? null),
        ];
    }

    private static function compareMetric(mixed $planned, mixed $actual): ?bool
    {
        $plannedEmpty = ! TrainingLoadSupport::hasNumericValue($planned);
        $actualEmpty = ! TrainingLoadSupport::hasNumericValue($actual);

        if ($plannedEmpty && $actualEmpty) {
            return null;
        }

        if ($plannedEmpty || $actualEmpty) {
            return false;
        }

        return TrainingLoadSupport::valuesMatch($planned, $actual);
    }

    /**
     * @param  array{sets: mixed, reps: mixed, load: mixed, load_percent: mixed, rpe: mixed}  $planned
     * @param  array{sets: mixed, reps: mixed, load: mixed, load_percent: mixed, rpe: mixed}  $actual
     */
    private static function compareLoad(array $planned, array $actual): ?bool
    {
        $plannedHasLoad = TrainingLoadSupport::hasNumericValue($planned['load'] ?? null)
            || TrainingLoadSupport::hasNumericValue($planned['load_percent'] ?? null);
        $actualHasLoad = TrainingLoadSupport::hasNumericValue($actual['load'] ?? null)
            || TrainingLoadSupport::hasNumericValue($actual['load_percent'] ?? null);

        if (! $plannedHasLoad && ! $actualHasLoad) {
            return null;
        }

        if (! $plannedHasLoad || ! $actualHasLoad) {
            return false;
        }

        return TrainingLoadSupport::loadsMatch($planned, $actual, [], 'squat');
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
            'set_scheme' => SetSchemeSupport::resolveScheme($exercise->set_scheme),
            'scheme_config' => is_array($exercise->scheme_config) ? $exercise->scheme_config : null,
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
        $schemeText = SetSchemeSupport::formatPrescription([
            'set_scheme' => $exercise->set_scheme,
            'scheme_config' => $exercise->scheme_config,
            'reps' => $exercise->reps,
        ]);
        if ($schemeText !== '') {
            return $schemeText;
        }

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
