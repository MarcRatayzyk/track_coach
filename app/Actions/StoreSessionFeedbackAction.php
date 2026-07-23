<?php

namespace App\Actions;

use App\Models\AthleteProfile;
use App\Models\DashboardTask;
use App\Models\ProgramDayExercise;
use App\Models\ProgramTrainingDay;
use App\Models\SessionFeedback;
use App\Models\SessionFeedbackMedia;
use App\Models\User;
use App\Notifications\NewSessionFeedbackNotification;
use App\Support\FeedbackFrequencySupport;
use App\Support\MailSendSupport;
use App\Support\SessionFeedbackPresenter;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreSessionFeedbackAction
{
    public function __construct(
        private readonly ResolveProgramSessionForDateAction $resolveSession,
        private readonly StoreSessionFeedbackMediaAction $storeMedia,
    ) {}

    /**
     * @param  list<UploadedFile>  $videos
     * @param  list<int>  $videoUploadIds
     * @param  list<int|null>  $videoSeries  Ids d'exercice choisis par vidéo (alignés sur l'ordre des vidéos).
     */
    public function execute(
        User $athlete,
        string $sessionDate,
        ?string $athleteNotes,
        array $videos = [],
        array $videoUploadIds = [],
        array $videoSeries = [],
    ): SessionFeedback {
        $date = Carbon::parse($sessionDate)->startOfDay();

        $resolved = $this->resolveSession->execute($athlete, $date);

        $duplicate = SessionFeedback::query()
            ->where('athlete_id', $athlete->id)
            ->where('program_training_day_id', $resolved['training_day']->id)
            ->whereDate('session_date', $date->toDateString())
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'session_date' => 'Un retour existe déjà pour cette séance programme.',
            ]);
        }

        if (
            FeedbackFrequencySupport::isWeekly($athlete)
            && FeedbackFrequencySupport::hasFeedbackForWeek($athlete, $date)
        ) {
            throw ValidationException::withMessages([
                'session_date' => 'Un retour existe déjà pour cette semaine.',
            ]);
        }

        return DB::transaction(function () use ($athlete, $date, $athleteNotes, $videos, $videoUploadIds, $videoSeries, $resolved): SessionFeedback {
            $feedback = SessionFeedback::query()->create([
                'coach_id' => $resolved['coach']->id,
                'athlete_id' => $athlete->id,
                'athlete_program_assignment_id' => $resolved['assignment']->id,
                'program_training_day_id' => $resolved['training_day']->id,
                'session_date' => $date->toDateString(),
                'athlete_notes' => $athleteNotes,
                'status' => SessionFeedback::STATUS_SUBMITTED,
                'submitted_at' => now(),
            ]);

            $seriesByPosition = $this->resolveSeriesSnapshots($resolved['training_day'], $videoSeries);

            if ($videoUploadIds !== []) {
                $this->storeMedia->attachUploadedVideos($feedback, $athlete, $videoUploadIds, $seriesByPosition);
            } elseif ($videos !== []) {
                $this->storeMedia->storeVideos($feedback, $videos, $athlete->id, $seriesByPosition);
            }

            $this->linkDashboardTask($feedback, $athlete, $date);

            $feedback->load(['athleteVideos', 'programTrainingDay', 'athlete:id,name', 'coach:id,name']);
            MailSendSupport::notifySafely($feedback->coach, new NewSessionFeedbackNotification($feedback));

            return $feedback;
        });
    }

    /**
     * Construit, par position de vidéo, le snapshot de série (exercice planifié).
     * Tout id d'exercice hors de la séance est ignoré (sécurité).
     *
     * @param  list<int|null>  $videoSeries
     * @return array<int, array{exercise_id:int, snapshot:array<string, mixed>}>
     */
    private function resolveSeriesSnapshots(ProgramTrainingDay $trainingDay, array $videoSeries): array
    {
        $ids = array_values(array_filter(
            array_map(static fn ($id) => $id === null ? null : (int) $id, $videoSeries),
            static fn ($id) => $id !== null,
        ));

        if ($ids === []) {
            return [];
        }

        $exercises = $trainingDay->exercises()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $result = [];
        foreach (array_values($videoSeries) as $position => $rawId) {
            $id = $rawId === null ? null : (int) $rawId;
            if ($id === null) {
                continue;
            }

            /** @var ProgramDayExercise|null $exercise */
            $exercise = $exercises->get($id);
            if ($exercise === null) {
                continue;
            }

            $result[$position] = [
                'exercise_id' => $exercise->id,
                'snapshot' => SessionFeedbackPresenter::seriesSnapshot($exercise),
            ];
        }

        return $result;
    }

    private function linkDashboardTask(SessionFeedback $feedback, User $athlete, Carbon $date): void
    {
        $frequency = $athlete->profile?->feedback_frequency ?? AthleteProfile::FREQUENCY_WEEKLY;

        $taskQuery = DashboardTask::query()
            ->where('coach_id', $feedback->coach_id)
            ->where('athlete_id', $feedback->athlete_id)
            ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
            ->where('status', 'pending');

        if ($frequency === AthleteProfile::FREQUENCY_DAILY) {
            $task = (clone $taskQuery)
                ->whereDate('session_date', $date->toDateString())
                ->whereNull('period_week_start')
                ->first();
        } else {
            $weekStart = $date->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
            $task = (clone $taskQuery)
                ->whereDate('period_week_start', $weekStart)
                ->first();
        }

        if ($task !== null) {
            $task->update(['session_feedback_id' => $feedback->id]);
        }
    }
}
