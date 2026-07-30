<?php

namespace App\Services;

use App\Models\AthleteProfile;
use App\Models\AthleteProgramAssignment;
use App\Models\AthleteReadinessForm;
use App\Models\CoachReadinessForm;
use App\Models\Competition;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\PersonalRecord;
use App\Models\ProgramDayExercise;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\SessionFeedback;
use App\Models\User;
use App\Support\FeedbackReplySupport;
use App\Support\MatchPlanData;
use App\Support\ReadinessFormSupport;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoSandboxProvisioner
{
    public function provision(User $coach): void
    {
        DB::transaction(function () use ($coach): void {
            $definitions = $this->athleteDefinitions($coach->id);
            $athletes = [];

            foreach ($definitions as $key => $definition) {
                $athletes[$key] = User::query()->create([
                    'name' => $definition['name'],
                    'email' => $definition['email'],
                    'password' => Str::password(48),
                    'role' => 'athlete',
                    'is_demo' => true,
                    'demo_expires_at' => $coach->demo_expires_at,
                    'initial_setup_completed_at' => now(),
                    'email_verified_at' => now(),
                ]);
            }

            foreach ($definitions as $key => $definition) {
                $athlete = $athletes[$key];
                $this->attachCoachAthlete($coach, $athlete);
                $this->seedAthleteProfile($athlete, $definition);
                $this->seedPrHistory($athlete, $definition['prs']);
                $this->seedCompetition($athlete, $definition);
            }

            $template = $this->seedMeetTemplate($coach);
            $cycleStart = now()->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(2)->startOfDay();
            $assignments = [];

            foreach ($definitions as $key => $definition) {
                $assignments[$key] = AthleteProgramAssignment::query()->create([
                    'athlete_id' => $athletes[$key]->id,
                    'template_id' => $template->id,
                    'date_start' => $cycleStart->toDateString(),
                    'date_end' => now()->addDays($definition['block_end_days'])->toDateString(),
                    'status' => 'active',
                ]);
            }

            $this->seedSessionFeedbacks($coach, $athletes, $assignments);
            $this->seedMessageThreads($coach, $athletes);
            $this->seedReadinessForms($coach, $athletes);
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function athleteDefinitions(int $coachId): array
    {
        $suffix = 'demo.'.$coachId.'.'.Str::lower(Str::random(6));

        return [
            'daily' => [
                'name' => 'Camille Bernard',
                'email' => "camille.{$suffix}@sandbox.powerroster.local",
                'weight_category' => 'f63',
                'sex' => 'female',
                'birth_date' => '1998-04-14',
                'bio' => 'Athlète démo — suivi quotidien, bloc meet en cours.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_DAILY,
                'prs' => ['squat' => 150, 'bench' => 90, 'deadlift' => 185],
                'competition_name' => 'Open de Lyon',
                'competition_days' => 12,
                'block_end_days' => 45,
                'competition_location' => 'Palais des Sports, Lyon',
            ],
            'weekly' => [
                'name' => 'Hugo Martin',
                'email' => "hugo.{$suffix}@sandbox.powerroster.local",
                'weight_category' => 'm93',
                'sex' => 'male',
                'birth_date' => '1994-09-02',
                'bio' => 'Athlète démo — profil force, point hebdo.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 215, 'bench' => 145, 'deadlift' => 250],
                'competition_name' => 'Coupe Grand Est',
                'competition_days' => 96,
                'block_end_days' => 55,
                'competition_location' => 'Complexe sportif, Metz',
                'seed_match_plan' => true,
            ],
            'return' => [
                'name' => 'Léa Petit',
                'email' => "lea.{$suffix}@sandbox.powerroster.local",
                'weight_category' => 'f76',
                'sex' => 'female',
                'birth_date' => '1997-01-19',
                'bio' => 'Athlète démo — retour progressif après coupure.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 175, 'bench' => 105, 'deadlift' => 205],
                'competition_name' => 'Challenge des clubs',
                'competition_days' => 140,
                'block_end_days' => 70,
                'competition_location' => 'Halle Diagana, Paris',
            ],
        ];
    }

    private function attachCoachAthlete(User $coach, User $athlete): void
    {
        DB::table('coach_athlete')->insert([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function seedAthleteProfile(User $athlete, array $definition): void
    {
        AthleteProfile::query()->create([
            'user_id' => $athlete->id,
            'birth_date' => $definition['birth_date'],
            'weight_category' => $definition['weight_category'],
            'sex' => $definition['sex'] ?? null,
            'bio' => $definition['bio'],
            'feedback_frequency' => $definition['feedback_frequency'],
            'level' => AthleteProfile::LEVEL_INTERMEDIATE,
        ]);
    }

    /**
     * @param  array{squat: int, bench: int, deadlift: int}  $prs
     */
    private function seedPrHistory(User $athlete, array $prs): void
    {
        foreach ([
            ['months' => 6, 'factor' => 0.90],
            ['months' => 3, 'factor' => 0.96],
            ['months' => 0, 'factor' => 1.00],
        ] as $entry) {
            PersonalRecord::query()->create([
                'athlete_id' => $athlete->id,
                'squat' => (int) round($prs['squat'] * $entry['factor']),
                'bench' => (int) round($prs['bench'] * $entry['factor']),
                'deadlift' => (int) round($prs['deadlift'] * $entry['factor']),
                'reference_date' => now()->subMonths($entry['months'])->toDateString(),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function seedCompetition(User $athlete, array $definition): void
    {
        $prs = $definition['prs'];
        $mainPlan = [
            'squat' => [
                'attempt1' => $this->roundToNearest($prs['squat'] * 0.90, 2.5),
                'attempt2' => $this->roundToNearest($prs['squat'] * 0.96, 2.5),
                'attempt3' => $this->roundToNearest($prs['squat'] * 1.01, 2.5),
            ],
            'bench' => [
                'attempt1' => $this->roundToNearest($prs['bench'] * 0.90, 2.5),
                'attempt2' => $this->roundToNearest($prs['bench'] * 0.96, 2.5),
                'attempt3' => $this->roundToNearest($prs['bench'] * 1.01, 2.5),
            ],
            'deadlift' => [
                'attempt1' => $this->roundToNearest($prs['deadlift'] * 0.90, 2.5),
                'attempt2' => $this->roundToNearest($prs['deadlift'] * 0.97, 2.5),
                'attempt3' => $this->roundToNearest($prs['deadlift'] * 1.02, 2.5),
            ],
        ];

        $matchPlanData = [
            'mode' => 'structured',
            'scenarios' => [
                [
                    'id' => 'scenario_main',
                    'name' => 'Plan principal',
                    'lifts' => $mainPlan,
                ],
            ],
        ];

        $goal = (string) (
            $mainPlan['squat']['attempt3']
            + $mainPlan['bench']['attempt3']
            + $mainPlan['deadlift']['attempt3']
        ).' total';

        $payload = [
            'athlete_id' => $athlete->id,
            'name' => $definition['competition_name'],
            'competition_date' => now()->addDays($definition['competition_days'])->toDateString(),
            'goal' => $goal,
            'location' => $definition['competition_location'],
        ];

        if ($definition['seed_match_plan'] ?? false) {
            $payload['match_plan_data'] = $matchPlanData;
            $payload['match_plan'] = MatchPlanData::toText($matchPlanData);
        }

        Competition::query()->create($payload);
    }

    private function seedMeetTemplate(User $coach): ProgramTemplate
    {
        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Démo sandbox — Peak 2 semaines',
            'goal' => 'Aperçu programmation SBD',
            'level' => 'intermediate',
        ]);

        for ($weekNumber = 1; $weekNumber <= 2; $weekNumber++) {
            $week = ProgramWeek::query()->create([
                'template_id' => $template->id,
                'week_number' => $weekNumber,
                'block_type' => $weekNumber === 1
                    ? ProgramWeek::BLOCK_INTENSIFICATION
                    : ProgramWeek::BLOCK_PEAKING,
            ]);

            foreach (
                [
                    ['day' => 2, 'lift' => ProgramTrainingDay::LIFT_SQUAT, 'name' => 'Squat', 'pct' => 75 + $weekNumber],
                    ['day' => 4, 'lift' => ProgramTrainingDay::LIFT_BENCH, 'name' => 'Bench', 'pct' => 72 + $weekNumber],
                    ['day' => 6, 'lift' => ProgramTrainingDay::LIFT_DEADLIFT, 'name' => 'Deadlift', 'pct' => 78 + $weekNumber],
                ] as $dayDef
            ) {
                $trainingDay = ProgramTrainingDay::query()->create([
                    'week_id' => $week->id,
                    'day_number' => $dayDef['day'],
                    'main_lift' => $dayDef['lift'],
                ]);

                ProgramDayExercise::query()->create([
                    'training_day_id' => $trainingDay->id,
                    'sort_order' => 1,
                    'section' => ProgramDayExercise::SECTION_TOPSET,
                    'exercise_name' => $dayDef['name'],
                    'lift' => $dayDef['lift'],
                    'sets' => 3,
                    'reps' => 3,
                    'load_percent' => $dayDef['pct'],
                    'rpe' => 8,
                ]);
            }
        }

        return $template;
    }

    /**
     * @param  array<string, User>  $athletes
     * @param  array<string, AthleteProgramAssignment>  $assignments
     */
    private function seedSessionFeedbacks(User $coach, array $athletes, array $assignments): void
    {
        foreach (['daily', 'weekly'] as $key) {
            if (! isset($athletes[$key], $assignments[$key])) {
                continue;
            }

            $assignment = $assignments[$key]->load('template.weeks.trainingDays');
            $day = $assignment->template?->weeks?->first()?->trainingDays?->first();

            if (! $day) {
                continue;
            }

            $feedback = SessionFeedback::query()->create([
                'coach_id' => $coach->id,
                'athlete_id' => $athletes[$key]->id,
                'athlete_program_assignment_id' => $assignment->id,
                'program_training_day_id' => $day->id,
                'session_date' => now()->subDay()->toDateString(),
                'athlete_notes' => $key === 'daily'
                    ? 'Top set propre. Pause bench mieux tenue — compte démo.'
                    : 'Semaine correcte. Bench stable — compte démo.',
                'status' => $key === 'daily'
                    ? SessionFeedback::STATUS_COACH_REPLIED
                    : SessionFeedback::STATUS_SUBMITTED,
                'submitted_at' => now()->subDay()->setTime(19, 30),
            ]);

            if ($key === 'daily') {
                FeedbackReplySupport::createCoachReply(
                    $feedback,
                    'Très propre. On garde la même exposition (réponse démo).',
                );
            }
        }
    }

    /**
     * @param  array<string, User>  $athletes
     */
    private function seedMessageThreads(User $coach, array $athletes): void
    {
        $athlete = $athletes['weekly'] ?? reset($athletes);
        if (! $athlete instanceof User) {
            return;
        }

        $thread = MessageThread::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);

        Message::query()->create([
            'thread_id' => $thread->id,
            'sender_id' => $athlete->id,
            'content' => 'Salut coach, je voulais valider les charges de la semaine — message démo.',
        ]);

        Message::query()->create([
            'thread_id' => $thread->id,
            'sender_id' => $coach->id,
            'content' => 'OK pour moi, on reste sur le plan. (réponse sandbox)',
        ]);
    }

    /**
     * @param  array<string, User>  $athletes
     */
    private function seedReadinessForms(User $coach, array $athletes): void
    {
        $fields = ReadinessFormSupport::defaultFields();

        CoachReadinessForm::query()->create([
            'coach_id' => $coach->id,
            'fields' => $fields,
        ]);

        foreach ($athletes as $athlete) {
            AthleteReadinessForm::query()->create([
                'athlete_id' => $athlete->id,
                'fields' => $fields,
            ]);
        }
    }

    private function roundToNearest(float $value, float $step): float
    {
        if ($step <= 0) {
            return $value;
        }

        return round($value / $step) * $step;
    }
}
