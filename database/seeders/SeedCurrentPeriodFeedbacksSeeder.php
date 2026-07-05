<?php

namespace Database\Seeders;

use App\Actions\SyncCoachFeedbackExpectations;
use App\Models\AthleteProfile;
use App\Models\AthleteProgramAssignment;
use App\Models\DashboardTask;
use App\Models\SessionFeedback;
use App\Support\FeedbackReplySupport;
use App\Models\User;
use App\Support\ProgramSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SeedCurrentPeriodFeedbacksSeeder extends Seeder
{
    public function run(): void
    {
        $coach = User::query()
            ->where('email', 'coach@trackcoach.dev')
            ->where('role', 'coach')
            ->first();

        if ($coach === null) {
            $this->command?->warn('Coach démo introuvable (coach@trackcoach.dev).');

            return;
        }

        app(SyncCoachFeedbackExpectations::class)->execute($coach);

        $today = now()->copy()->startOfDay();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $weekEnd = $today->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $this->seedDailyFeedbacksForToday($coach, $today);
        $this->seedWeeklyFeedbacksForCurrentWeek($coach, $weekStart, $weekEnd, $today);

        $this->command?->info('Retours du jour et de la semaine en cours mis à jour.');
    }

    private function seedDailyFeedbacksForToday(User $coach, Carbon $today): void
    {
        $athletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->get();

        $dailyIndex = 0;

        foreach ($athletes as $athlete) {
            $assignment = $this->activeAssignment($athlete->id, $today);
            if ($assignment === null || ! ProgramSchedule::hasSessionOnDate($assignment, $today)) {
                continue;
            }

            $trainingDay = ProgramSchedule::resolveTrainingDayForDate($assignment, $today);
            if ($trainingDay === null) {
                continue;
            }

            $task = DashboardTask::query()
                ->where('coach_id', $coach->id)
                ->where('athlete_id', $athlete->id)
                ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
                ->whereDate('session_date', $today->toDateString())
                ->whereNull('period_week_start')
                ->first();

            if ($task === null) {
                continue;
            }

            $feedback = SessionFeedback::query()
                ->where('coach_id', $coach->id)
                ->where('athlete_id', $athlete->id)
                ->whereDate('session_date', $today->toDateString())
                ->first();

            if ($feedback === null) {
                if ($dailyIndex % 4 === 3) {
                    $dailyIndex++;

                    continue;
                }

                $withReply = $dailyIndex % 2 === 0;
                $submittedAt = $today->copy()->setTime(8 + ($dailyIndex % 6), 15 + ($dailyIndex * 7) % 45);

                $feedback = SessionFeedback::query()->create([
                    'coach_id' => $coach->id,
                    'athlete_id' => $athlete->id,
                    'athlete_program_assignment_id' => $assignment->id,
                    'program_training_day_id' => $trainingDay->id,
                    'session_date' => $today->toDateString(),
                    'athlete_notes' => $this->dailyNote($athlete->name, $dailyIndex),
                    'status' => $withReply
                        ? SessionFeedback::STATUS_COACH_REPLIED
                        : SessionFeedback::STATUS_SUBMITTED,
                    'submitted_at' => $submittedAt,
                ]);

                if ($withReply) {
                    FeedbackReplySupport::createCoachReply(
                        $feedback,
                        'Bien reçu. On garde cette trajectoire, envoie la vidéo du top set si ce n’est pas déjà fait.',
                    );
                }
            }

            $task->update(['session_feedback_id' => $feedback->id]);
            $dailyIndex++;
        }
    }

    private function seedWeeklyFeedbacksForCurrentWeek(
        User $coach,
        Carbon $weekStart,
        Carbon $weekEnd,
        Carbon $today,
    ): void {
        $athletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->with('profile')
            ->get();

        $weeklyIndex = 0;

        foreach ($athletes as $athlete) {
            $frequency = $athlete->profile?->feedback_frequency ?? AthleteProfile::FREQUENCY_WEEKLY;
            if ($frequency !== AthleteProfile::FREQUENCY_WEEKLY) {
                continue;
            }

            $assignment = $this->activeAssignment($athlete->id, $weekEnd);
            if ($assignment === null || ! ProgramSchedule::hasAnySessionBetween($assignment, $weekStart, $weekEnd)) {
                continue;
            }

            $task = DashboardTask::query()
                ->where('coach_id', $coach->id)
                ->where('athlete_id', $athlete->id)
                ->where('type', DashboardTask::TYPE_FEEDBACK_SESSION)
                ->whereDate('period_week_start', $weekStart->toDateString())
                ->first();

            if ($task === null) {
                continue;
            }

            if ($task->session_feedback_id !== null) {
                continue;
            }

            $existingWeekFeedback = SessionFeedback::query()
                ->where('coach_id', $coach->id)
                ->where('athlete_id', $athlete->id)
                ->whereDate('session_date', '>=', $weekStart->toDateString())
                ->whereDate('session_date', '<=', $weekEnd->toDateString())
                ->first();

            if ($existingWeekFeedback !== null) {
                $task->update(['session_feedback_id' => $existingWeekFeedback->id]);
                $weeklyIndex++;

                continue;
            }

            if ($weeklyIndex % 3 === 2) {
                $weeklyIndex++;

                continue;
            }

            [$sessionDate, $trainingDay] = $this->resolveWeeklyFeedbackSlot($assignment, $weekStart, $weekEnd);

            if ($sessionDate === null || $trainingDay === null) {
                $weeklyIndex++;

                continue;
            }

            $withReply = $weeklyIndex % 2 === 1;

            $feedback = SessionFeedback::query()->create([
                'coach_id' => $coach->id,
                'athlete_id' => $athlete->id,
                'athlete_program_assignment_id' => $assignment->id,
                'program_training_day_id' => $trainingDay->id,
                'session_date' => $sessionDate->toDateString(),
                'athlete_notes' => $this->weeklyNote($athlete->name, $weeklyIndex),
                'status' => $withReply
                    ? SessionFeedback::STATUS_COACH_REPLIED
                    : SessionFeedback::STATUS_SUBMITTED,
                'submitted_at' => $sessionDate->copy()->setTime(20, 10 + ($weeklyIndex * 11) % 50),
            ]);

            if ($withReply) {
                FeedbackReplySupport::createCoachReply(
                    $feedback,
                    'Bon point hebdo. On conserve le plan actuel et on réévalue les charges lundi.',
                );
            }

            $task->update(['session_feedback_id' => $feedback->id]);
            $weeklyIndex++;
        }
    }

    /**
     * @return array{0: ?Carbon, 1: ?\App\Models\ProgramTrainingDay}
     */
    private function resolveWeeklyFeedbackSlot(
        AthleteProgramAssignment $assignment,
        Carbon $weekStart,
        Carbon $weekEnd,
    ): array {
        $cursor = $weekStart->copy()->startOfDay();

        while ($cursor->lte($weekEnd)) {
            if (! ProgramSchedule::hasSessionOnDate($assignment, $cursor)) {
                $cursor->addDay();

                continue;
            }

            $trainingDay = ProgramSchedule::resolveTrainingDayForDate($assignment, $cursor);
            if ($trainingDay === null) {
                $cursor->addDay();

                continue;
            }

            $alreadyExists = SessionFeedback::query()
                ->where('athlete_id', $assignment->athlete_id)
                ->where('program_training_day_id', $trainingDay->id)
                ->whereDate('session_date', $cursor->toDateString())
                ->exists();

            if (! $alreadyExists) {
                return [$cursor->copy(), $trainingDay];
            }

            $cursor->addDay();
        }

        return [null, null];
    }

    private function activeAssignment(int $athleteId, Carbon $referenceDate): ?AthleteProgramAssignment
    {
        return AthleteProgramAssignment::query()
            ->where('athlete_id', $athleteId)
            ->where('status', 'active')
            ->whereDate('date_start', '<=', $referenceDate->toDateString())
            ->where(function ($query) use ($referenceDate): void {
                $query->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $referenceDate->toDateString());
            })
            ->with('template.weeks.trainingDays')
            ->first();
    }

    private function dailyNote(string $athleteName, int $index): string
    {
        $notes = [
            'Top set propre ce matin, bonne vitesse en sortie de trou au squat.',
            'Bench stable, pause bien tenue. Légère fatigue sur les accessoires.',
            'Deadlift un peu lent en fin de séance mais technique propre. Vidéo jointe.',
            'Séance complète sans douleur, RPE conforme au plan.',
            'Squat pause en progrès, j’ai besoin de ton avis sur la profondeur.',
        ];

        return $notes[$index % count($notes)]." — {$athleteName}";
    }

    private function weeklyNote(string $athleteName, int $index): string
    {
        $notes = [
            'Bonne semaine globalement, récupération correcte malgré le volume.',
            'Le bench progresse, squat encore irrégulier sur les séries lourdes.',
            'Fatigue modérée en fin de semaine, j’ai respecté les charges prescrites.',
            'Semaine solide, prêt à monter légèrement sur le deadlift.',
        ];

        return $notes[$index % count($notes)]." — {$athleteName}";
    }
}
