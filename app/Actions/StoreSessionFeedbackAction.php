<?php

namespace App\Actions;

use App\Models\AthleteProfile;
use App\Models\DashboardTask;
use App\Models\SessionFeedback;
use App\Models\User;
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
     */
    public function execute(
        User $athlete,
        string $sessionDate,
        ?string $athleteNotes,
        array $videos,
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

        return DB::transaction(function () use ($athlete, $date, $athleteNotes, $videos, $resolved): SessionFeedback {
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

            $this->storeMedia->storeVideos($feedback, $videos);
            $this->linkDashboardTask($feedback, $athlete, $date);

            return $feedback->load(['athleteVideos', 'programTrainingDay', 'athlete:id,name']);
        });
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
