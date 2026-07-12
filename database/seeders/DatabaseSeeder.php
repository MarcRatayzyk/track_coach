<?php
namespace Database\Seeders;

use App\Models\AthleteProfile;
use App\Models\AthleteReadinessEntry;
use App\Models\AthleteProgramAssignment;
use App\Models\Competition;
use App\Models\DashboardTask;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\PersonalRecord;
use App\Models\ProgramDayExercise;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\SessionFeedback;
use App\Support\FeedbackReplySupport;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\MatchPlanData;
use App\Support\TrainingSessionSupport;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ExerciseLibrarySeeder::class);

        DB::transaction(function (): void {
            $coach = $this->upsertUser([
                'name' => 'Coach Demo',
                'email' => 'coach@trackcoach.dev',
                'role' => 'coach',
            ]);

            $athleteDefinitions = $this->athleteDefinitions();
            $athletes = [];

            foreach ($athleteDefinitions as $key => $definition) {
                $athletes[$key] = $this->upsertUser([
                    'name' => $definition['name'],
                    'email' => $definition['email'],
                    'role' => 'athlete',
                ]);
            }

            $this->resetDemoDataset($coach, $athletes);

            foreach ($athleteDefinitions as $key => $definition) {
                $athlete = $athletes[$key];

                $this->attachCoachAthlete($coach, $athlete);
                $this->seedAthleteProfile($athlete, $definition);
                $this->seedPrHistory($athlete, $definition['prs']);
                $this->seedCompetition($athlete, $definition);
            }

            $template = $this->seedMeetTemplate($coach);
            $cycleStart = now()->copy()->startOfWeek(Carbon::MONDAY)->subWeeks(5)->startOfDay();
            $assignments = [];

            foreach ($athleteDefinitions as $key => $definition) {
                $assignment = AthleteProgramAssignment::query()->create([
                    'athlete_id' => $athletes[$key]->id,
                    'template_id' => $template->id,
                    'date_start' => $cycleStart->toDateString(),
                    'date_end' => now()->addDays($definition['block_end_days'])->toDateString(),
                    'status' => 'active',
                ]);

                $assignments[$key] = $assignment;

                $this->seedTrainingHistory(
                    $athletes[$key],
                    $assignment->load('template.weeks.trainingDays.exercises'),
                    $definition['prs'],
                    $definition['skip_sessions'],
                );
            }

            $this->seedSessionFeedbacks($coach, $athletes, $assignments);
            $this->call(SeedCurrentPeriodFeedbacksSeeder::class);
            $this->seedMessageThreads($coach, $athletes);
            $this->seedReadinessEntries($athletes);
        });
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function athleteDefinitions(): array
    {
        return [
            'daily' => [
                'name' => 'Camille Bernard',
                'email' => 'daily@trackcoach.dev',
                'weight_category' => 'f63',
                'sex' => 'female',
                'birth_date' => '1998-04-14',
                'bio' => 'Athlete suivie au quotidien, bloc meet en cours avec video sur les topsets.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_DAILY,
                'prs' => ['squat' => 150, 'bench' => 90, 'deadlift' => 185],
                'competition_name' => 'Open de Lyon',
                'competition_days' => 5,
                'block_end_days' => 6,
                'competition_location' => 'Palais des Sports, Lyon',
                'skip_sessions' => ['3-6'],
            ],
            'weekly' => [
                'name' => 'Hugo Martin',
                'email' => 'athlete@trackcoach.dev',
                'weight_category' => 'm93',
                'sex' => 'male',
                'birth_date' => '1994-09-02',
                'bio' => 'Profil force confirmé, point hebdo et ajustements surtout sur le bench.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 215, 'bench' => 145, 'deadlift' => 250],
                'competition_name' => 'Coupe Grand Est',
                'competition_days' => 14,
                'block_end_days' => 11,
                'competition_location' => 'Complexe sportif Marcel-Cerdan, Metz',
                'seed_match_plan' => true,
                'skip_sessions' => ['2-4', '4-6'],
            ],
            'return' => [
                'name' => 'Léa Petit',
                'email' => 'return@trackcoach.dev',
                'weight_category' => 'f76',
                'sex' => 'female',
                'birth_date' => '1997-01-19',
                'bio' => 'Retour progressif après coupure, priorité sur la régularité et la technique.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 175, 'bench' => 105, 'deadlift' => 205],
                'competition_name' => 'Challenge des clubs',
                'competition_days' => 22,
                'block_end_days' => 3,
                'competition_location' => 'Halle Diagana, Paris',
                'skip_sessions' => ['1-6', '3-4', '5-6', '6-4'],
            ],
            'thomas' => [
                'name' => 'Thomas Dubois',
                'email' => 'thomas@trackcoach.dev',
                'weight_category' => 'm83',
                'sex' => 'male',
                'birth_date' => '1992-06-11',
                'bio' => 'Force athlétique confirmée, travail sur la vitesse au squat et la stabilité au bench.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 195, 'bench' => 130, 'deadlift' => 230],
                'competition_name' => 'Championnat Régional IDF',
                'competition_days' => 18,
                'block_end_days' => 9,
                'competition_location' => 'Gymnase Pierre-de-Coubertin, Paris',
                'seed_match_plan' => true,
                'skip_sessions' => ['2-6'],
            ],
            'sarah' => [
                'name' => 'Sarah Moreau',
                'email' => 'sarah@trackcoach.dev',
                'weight_category' => 'f57',
                'sex' => 'female',
                'birth_date' => '2000-03-28',
                'bio' => 'Jeune athlète prometteuse, suivi quotidien pour consolider la technique sur les trois lifts.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_DAILY,
                'prs' => ['squat' => 125, 'bench' => 72, 'deadlift' => 155],
                'competition_name' => 'Open de Toulouse',
                'competition_days' => 8,
                'block_end_days' => 5,
                'competition_location' => 'Palais des Sports, Toulouse',
                'skip_sessions' => [],
            ],
            'nicolas' => [
                'name' => 'Nicolas Leroy',
                'email' => 'nicolas@trackcoach.dev',
                'weight_category' => 'm105',
                'sex' => 'male',
                'birth_date' => '1989-11-05',
                'bio' => 'Powerlifter expérimenté, focus sur le pic de deadlift et la gestion de la fatigue en fin de bloc.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 240, 'bench' => 165, 'deadlift' => 280],
                'competition_name' => 'Nationaux Force Athlétique',
                'competition_days' => 28,
                'block_end_days' => 14,
                'competition_location' => 'Arena de Bercy, Paris',
                'seed_match_plan' => true,
                'skip_sessions' => ['3-2', '5-4'],
            ],
            'emma' => [
                'name' => 'Emma Rousseau',
                'email' => 'emma@trackcoach.dev',
                'weight_category' => 'f69',
                'sex' => 'female',
                'birth_date' => '1996-08-17',
                'bio' => 'Profil équilibré sur les trois mouvements, progression régulière depuis 18 mois.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 165, 'bench' => 95, 'deadlift' => 195],
                'competition_name' => 'Coupe de Bretagne',
                'competition_days' => 12,
                'block_end_days' => 7,
                'competition_location' => 'Salle Omnisports, Rennes',
                'skip_sessions' => ['4-4'],
            ],
            'antoine' => [
                'name' => 'Antoine Girard',
                'email' => 'antoine@trackcoach.dev',
                'weight_category' => 'm120',
                'sex' => 'male',
                'birth_date' => '1987-02-22',
                'bio' => 'Catégorie lourde, priorité au volume contrôlé et à la mobilité hanche pour le squat.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 255, 'bench' => 175, 'deadlift' => 295],
                'competition_name' => 'Open de Marseille',
                'competition_days' => 35,
                'block_end_days' => 18,
                'competition_location' => 'Palais Omnisports, Marseille',
                'seed_match_plan' => true,
                'skip_sessions' => ['1-4', '2-2'],
            ],
            'julie' => [
                'name' => 'Julie Lambert',
                'email' => 'julie@trackcoach.dev',
                'weight_category' => 'f47',
                'sex' => 'female',
                'birth_date' => '2002-12-03',
                'bio' => 'Catégorie plume, travail technique intensif avec vidéos sur chaque top set.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_DAILY,
                'prs' => ['squat' => 105, 'bench' => 60, 'deadlift' => 130],
                'competition_name' => 'Challenge Féminin Nord',
                'competition_days' => 6,
                'block_end_days' => 4,
                'competition_location' => 'Complexe sportif, Lille',
                'skip_sessions' => ['3-6'],
            ],
            'maxime' => [
                'name' => 'Maxime Fontaine',
                'email' => 'maxime@trackcoach.dev',
                'weight_category' => 'm74',
                'sex' => 'male',
                'birth_date' => '1995-07-09',
                'bio' => 'Bon potentiel au bench, squat en progression. Bloc meet classique sur 6 semaines.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 180, 'bench' => 125, 'deadlift' => 215],
                'competition_name' => 'Open de Bordeaux',
                'competition_days' => 16,
                'block_end_days' => 10,
                'competition_location' => 'Palais des Sports, Bordeaux',
                'skip_sessions' => ['2-4', '3-4'],
            ],
            'chloe' => [
                'name' => 'Chloé Bertrand',
                'email' => 'chloe@trackcoach.dev',
                'weight_category' => 'f84',
                'sex' => 'female',
                'birth_date' => '1993-04-25',
                'bio' => 'Athlète polyvalente, retour de blessure au genou. Progression prudente sur le squat.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 155, 'bench' => 100, 'deadlift' => 190],
                'competition_name' => 'Coupe Auvergne-Rhône-Alpes',
                'competition_days' => 20,
                'block_end_days' => 8,
                'competition_location' => 'Halle Tony-Garnier, Lyon',
                'seed_match_plan' => true,
                'skip_sessions' => ['1-2', '4-6', '5-2'],
            ],
            'lucas' => [
                'name' => 'Lucas Perrin',
                'email' => 'lucas@trackcoach.dev',
                'weight_category' => 'm59',
                'sex' => 'male',
                'birth_date' => '1999-10-14',
                'bio' => 'Catégorie légère, excellente technique au deadlift. Point hebdo sur les charges.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
                'prs' => ['squat' => 140, 'bench' => 85, 'deadlift' => 175],
                'competition_name' => 'Open de Nantes',
                'competition_days' => 10,
                'block_end_days' => 6,
                'competition_location' => 'Salle de la Trocardière, Nantes',
                'skip_sessions' => ['5-6'],
            ],
            'ines' => [
                'name' => 'Inès Renault',
                'email' => 'ines@trackcoach.dev',
                'weight_category' => 'f76',
                'sex' => 'female',
                'birth_date' => '1998-01-30',
                'bio' => 'Suivi quotidien, focus sur la constance et la récupération entre les séances lourdes.',
                'feedback_frequency' => AthleteProfile::FREQUENCY_DAILY,
                'prs' => ['squat' => 170, 'bench' => 100, 'deadlift' => 200],
                'competition_name' => 'Open de Strasbourg',
                'competition_days' => 7,
                'block_end_days' => 5,
                'competition_location' => 'Palais des Sports, Strasbourg',
                'skip_sessions' => ['2-6'],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertUser(array $attributes): User
    {
        return User::query()->updateOrCreate(
            ['email' => $attributes['email']],
            [
                'name' => $attributes['name'],
                'password' => 'password',
                'role' => $attributes['role'],
                'initial_setup_completed_at' => now(),
                'email_verified_at' => now(),
            ],
        );
    }

    /**
     * @param  array<string, User>  $athletes
     */
    private function resetDemoDataset(User $coach, array $athletes): void
    {
        $athleteIds = array_map(static fn (User $athlete): int => $athlete->id, $athletes);

        DashboardTask::query()
            ->where('coach_id', $coach->id)
            ->whereIn('athlete_id', $athleteIds)
            ->delete();

        MessageThread::query()
            ->where('coach_id', $coach->id)
            ->whereIn('athlete_id', $athleteIds)
            ->delete();

        SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereIn('athlete_id', $athleteIds)
            ->delete();

        TrainingSession::query()->whereIn('athlete_id', $athleteIds)->delete();
        AthleteReadinessEntry::query()->whereIn('athlete_id', $athleteIds)->delete();
        PersonalRecord::query()->whereIn('athlete_id', $athleteIds)->delete();
        Competition::query()->whereIn('athlete_id', $athleteIds)->delete();
        AthleteProgramAssignment::query()->whereIn('athlete_id', $athleteIds)->delete();
        AthleteProfile::query()->whereIn('user_id', $athleteIds)->delete();

        DB::table('coach_athlete')
            ->where('coach_id', $coach->id)
            ->whereIn('athlete_id', $athleteIds)
            ->delete();

        ProgramTemplate::query()
            ->where('coach_id', $coach->id)
            ->where('name', $this->demoTemplateName())
            ->delete();
    }

    private function attachCoachAthlete(User $coach, User $athlete): void
    {
        DB::table('coach_athlete')->updateOrInsert(
            ['coach_id' => $coach->id, 'athlete_id' => $athlete->id],
            [
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
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
        ]);
    }

    /**
     * @param  array{squat: int, bench: int, deadlift: int}  $prs
     */
    private function seedPrHistory(User $athlete, array $prs): void
    {
        $factors = [
            ['months' => 8, 'factor' => 0.86],
            ['months' => 6, 'factor' => 0.90],
            ['months' => 4, 'factor' => 0.94],
            ['months' => 2, 'factor' => 0.97],
            ['months' => 1, 'factor' => 0.99],
            ['months' => 0, 'factor' => 1.00],
        ];

        foreach ($factors as $entry) {
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

        $planB = [
            'squat' => [
                'attempt1' => $this->roundToNearest($prs['squat'] * 0.87, 2.5),
                'attempt2' => $this->roundToNearest($prs['squat'] * 0.93, 2.5),
                'attempt3' => $this->roundToNearest($prs['squat'] * 0.98, 2.5),
            ],
            'bench' => [
                'attempt1' => $this->roundToNearest($prs['bench'] * 0.87, 2.5),
                'attempt2' => $this->roundToNearest($prs['bench'] * 0.93, 2.5),
                'attempt3' => $this->roundToNearest($prs['bench'] * 0.98, 2.5),
            ],
            'deadlift' => [
                'attempt1' => $this->roundToNearest($prs['deadlift'] * 0.88, 2.5),
                'attempt2' => $this->roundToNearest($prs['deadlift'] * 0.94, 2.5),
                'attempt3' => $this->roundToNearest($prs['deadlift'] * 0.99, 2.5),
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
                [
                    'id' => 'scenario_conservative',
                    'name' => 'Plan B',
                    'lifts' => $planB,
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

    private function demoTemplateName(): string
    {
        return 'Démo - Peak meet 6 semaines';
    }

    private function seedMeetTemplate(User $coach): ProgramTemplate
    {
        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => $this->demoTemplateName(),
            'goal' => 'Construire un pic propre sur 6 semaines',
            'level' => 'intermediate',
        ]);

        $squatTopReps = [5, 5, 4, 4, 3, 2];
        $squatTopPct = [68, 71, 74, 77, 80, 83];
        $squatBackoffSets = [4, 4, 4, 3, 3, 2];
        $squatBackoffReps = [6, 5, 5, 4, 4, 3];
        $squatBackoffPct = [63, 66, 69, 72, 75, 78];

        $benchTopReps = [5, 5, 4, 4, 3, 2];
        $benchTopPct = [70, 72, 75, 78, 81, 84];
        $benchBackoffSets = [4, 4, 4, 3, 3, 2];
        $benchBackoffReps = [6, 5, 5, 4, 4, 3];
        $benchBackoffPct = [65, 67, 70, 72, 75, 79];

        $deadliftTopReps = [4, 4, 3, 3, 2, 1];
        $deadliftTopPct = [70, 73, 76, 79, 83, 87];
        $deadliftBackoffSets = [3, 3, 3, 3, 2, 2];
        $deadliftBackoffReps = [6, 6, 5, 5, 4, 3];
        $deadliftBackoffPct = [58, 60, 63, 66, 69, 72];

        for ($weekNumber = 1; $weekNumber <= 6; $weekNumber++) {
            $blockType = match (true) {
                $weekNumber <= 2 => ProgramWeek::BLOCK_VOLUME,
                $weekNumber <= 4 => ProgramWeek::BLOCK_INTENSIFICATION,
                default => ProgramWeek::BLOCK_PEAKING,
            };

            $week = ProgramWeek::query()->create([
                'template_id' => $template->id,
                'week_number' => $weekNumber,
                'block_type' => $blockType,
            ]);

            $squatDay = ProgramTrainingDay::query()->create([
                'week_id' => $week->id,
                'day_number' => 2,
                'main_lift' => ProgramTrainingDay::LIFT_SQUAT,
                'session_label' => 'Lower comp',
            ]);

            $this->createProgramExercise($squatDay, 0, ProgramDayExercise::SECTION_TOPSET, 'Squat pause', 'squat', 1, $squatTopReps[$weekNumber - 1], null, $squatTopPct[$weekNumber - 1]);
            $this->createProgramExercise($squatDay, 1, ProgramDayExercise::SECTION_BACKOFF, 'Squat', 'squat', $squatBackoffSets[$weekNumber - 1], $squatBackoffReps[$weekNumber - 1], null, $squatBackoffPct[$weekNumber - 1]);
            $this->createProgramExercise($squatDay, 2, ProgramDayExercise::SECTION_ACCESSORY, 'Leg press', 'squat', 3, 10);
            $this->createProgramExercise($squatDay, 3, ProgramDayExercise::SECTION_ACCESSORY, 'Rowing haltère', 'squat', 4, 10);

            $benchDay = ProgramTrainingDay::query()->create([
                'week_id' => $week->id,
                'day_number' => 4,
                'main_lift' => ProgramTrainingDay::LIFT_BENCH,
                'session_label' => 'Upper comp',
            ]);

            $this->createProgramExercise($benchDay, 0, ProgramDayExercise::SECTION_TOPSET, 'Bench pause', 'bench', 1, $benchTopReps[$weekNumber - 1], null, $benchTopPct[$weekNumber - 1]);
            $this->createProgramExercise($benchDay, 1, ProgramDayExercise::SECTION_BACKOFF, 'Comp Bench', 'bench', $benchBackoffSets[$weekNumber - 1], $benchBackoffReps[$weekNumber - 1], null, $benchBackoffPct[$weekNumber - 1]);
            $this->createProgramExercise($benchDay, 2, ProgramDayExercise::SECTION_ACCESSORY, 'Larsen press', 'bench', 3, 8);
            $this->createProgramExercise($benchDay, 3, ProgramDayExercise::SECTION_ACCESSORY, 'Skull crusher', 'bench', 3, 12);

            $deadliftDay = ProgramTrainingDay::query()->create([
                'week_id' => $week->id,
                'day_number' => 6,
                'main_lift' => ProgramTrainingDay::LIFT_DEADLIFT,
                'session_label' => 'Pull & posterior',
            ]);

            $this->createProgramExercise($deadliftDay, 0, ProgramDayExercise::SECTION_TOPSET, 'Deadlift conventionnel', 'deadlift', 1, $deadliftTopReps[$weekNumber - 1], null, $deadliftTopPct[$weekNumber - 1]);
            $this->createProgramExercise($deadliftDay, 1, ProgramDayExercise::SECTION_BACKOFF, 'Romanian deadlift', 'deadlift', $deadliftBackoffSets[$weekNumber - 1], $deadliftBackoffReps[$weekNumber - 1], null, $deadliftBackoffPct[$weekNumber - 1]);
            $this->createProgramExercise($deadliftDay, 2, ProgramDayExercise::SECTION_ACCESSORY, 'Hyperextension', 'deadlift', 3, 12);
            $this->createProgramExercise($deadliftDay, 3, ProgramDayExercise::SECTION_ACCESSORY, 'Tirage poulie', 'deadlift', 4, 10);
        }

        return $template->load('weeks.trainingDays.exercises');
    }

    private function createProgramExercise(
        ProgramTrainingDay $day,
        int $sortOrder,
        string $section,
        string $exerciseName,
        string $lift,
        int $sets,
        int $reps,
        ?float $load = null,
        ?float $loadPercent = null,
    ): void {
        ProgramDayExercise::query()->create([
            'training_day_id' => $day->id,
            'block_index' => 0,
            'lift' => $lift,
            'section' => $section,
            'exercise_name' => $exerciseName,
            'sets' => $sets,
            'reps' => $reps,
            'load' => $load,
            'load_percent' => $loadPercent,
            'sort_order' => $sortOrder,
        ]);
    }

    /**
     * @param  array{squat: int, bench: int, deadlift: int}  $oneRm
     * @param  list<string>  $skipSessions
     */
    private function seedTrainingHistory(
        User $athlete,
        AthleteProgramAssignment $assignment,
        array $oneRm,
        array $skipSessions,
    ): void {
        $today = now()->copy()->startOfDay();
        $template = $assignment->template;

        if ($template === null) {
            return;
        }

        foreach ($template->weeks as $week) {
            foreach ($week->trainingDays as $day) {
                $sessionDate = $assignment->date_start
                    ->copy()
                    ->startOfDay()
                    ->addWeeks($week->week_number - 1)
                    ->addDays($day->day_number - 1);

                if ($sessionDate->gt($today)) {
                    continue;
                }

                $key = "{$week->week_number}-{$day->day_number}";
                if (in_array($key, $skipSessions, true)) {
                    continue;
                }

                $session = new TrainingSession([
                    'athlete_id' => $athlete->id,
                ]);

                TrainingSessionSupport::applyValidated($session, [
                    'session_date' => $sessionDate->toDateString(),
                    'session_label' => $day->session_label,
                    'main_lift' => $day->main_lift,
                    'items' => $this->buildActualItems($day, $oneRm),
                    'notes' => $this->sessionNote($day->main_lift, $week->week_number),
                ]);

                $session->save();
            }
        }
    }

    /**
     * @param  array{squat: int, bench: int, deadlift: int}  $oneRm
     * @return list<array<string, mixed>>
     */
    private function buildActualItems(ProgramTrainingDay $day, array $oneRm): array
    {
        $items = [];

        foreach ($day->exercises as $exercise) {
            $load = $exercise->load !== null
                ? (float) $exercise->load
                : $this->actualLoadFromTemplate($exercise, $oneRm, $day->main_lift);

            $items[] = [
                'section' => $exercise->section,
                'exercise_variant_id' => $exercise->exercise_variant_id,
                'exercise_name' => $exercise->exercise_name,
                'lift' => $exercise->lift ?? $day->main_lift,
                'sets' => $exercise->sets,
                'reps' => $exercise->reps,
                'load' => $load,
            ];
        }

        return $items;
    }

    /**
     * @param  array{squat: int, bench: int, deadlift: int}  $oneRm
     */
    private function actualLoadFromTemplate(
        ProgramDayExercise $exercise,
        array $oneRm,
        string $defaultLift,
    ): ?float {
        $lift = $exercise->lift ?: $defaultLift;

        if ($exercise->load_percent !== null) {
            $rm = (float) ($oneRm[$lift] ?? 0);
            if ($rm <= 0) {
                return null;
            }

            return $this->roundToNearest(($exercise->load_percent / 100) * $rm, 5);
        }

        return $this->accessoryLoad($exercise->exercise_name, $oneRm, $lift);
    }

    /**
     * @param  array{squat: int, bench: int, deadlift: int}  $oneRm
     */
    private function accessoryLoad(string $exerciseName, array $oneRm, string $lift): ?float
    {
        $name = strtolower($exerciseName);

        return match (true) {
            str_contains($name, 'leg press') => $this->roundToNearest($oneRm['squat'] * 1.15, 5),
            str_contains($name, 'rowing') => $this->roundToNearest($oneRm['deadlift'] * 0.22, 5),
            str_contains($name, 'larsen') => $this->roundToNearest($oneRm['bench'] * 0.72, 5),
            str_contains($name, 'skull') => $this->roundToNearest($oneRm['bench'] * 0.35, 5),
            str_contains($name, 'hyperextension') => $this->roundToNearest($oneRm['deadlift'] * 0.18, 5),
            str_contains($name, 'tirage') => $this->roundToNearest($oneRm['deadlift'] * 0.24, 5),
            default => $oneRm[$lift] > 0 ? $this->roundToNearest($oneRm[$lift] * 0.40, 5) : null,
        };
    }

    private function sessionNote(string $mainLift, int $weekNumber): string
    {
        return match ($mainLift) {
            'squat' => "Semaine {$weekNumber} : rester propre sous fatigue, accent sur le gainage.",
            'bench' => "Semaine {$weekNumber} : pause marquée et trajectoire constante à chaque rep.",
            'deadlift' => "Semaine {$weekNumber} : verrouillage net, conserver de la vitesse en sortie de sol.",
            default => "Semaine {$weekNumber} : exécution propre et marge contrôlée.",
        };
    }

    /**
     * @param  array<string, User>  $athletes
     * @param  array<string, AthleteProgramAssignment>  $assignments
     */
    private function seedSessionFeedbacks(
        User $coach,
        array $athletes,
        array $assignments,
    ): void {
        $feedbackConfigs = [
            'daily' => [
                'moment_index' => 0,
                'athlete_notes' => 'Top set propre. La pause bench est mieux tenue, vidéo jointe sur le dernier single.',
                'status' => SessionFeedback::STATUS_COACH_REPLIED,
                'reply' => 'Très propre. On garde la même exposition semaine prochaine et on monte seulement le deadlift.',
                'hour' => 19,
                'minute' => 40,
            ],
            'weekly' => [
                'moment_index' => 0,
                'athlete_notes' => 'Semaine correcte malgré la fatigue. Le bench reste le lift le plus stable.',
                'status' => SessionFeedback::STATUS_SUBMITTED,
                'reply' => null,
                'hour' => 21,
                'minute' => 5,
            ],
            'return' => [
                'moment_index' => 1,
                'athlete_notes' => 'Bonne reprise. Encore un peu de retenue en descente sur le squat pause.',
                'status' => SessionFeedback::STATUS_COACH_REPLIED,
                'reply' => 'Nickel pour une semaine de reprise. On ne cherche pas plus lourd, seulement plus régulier.',
                'hour' => 18,
                'minute' => 30,
            ],
            'thomas' => [
                'moment_index' => 0,
                'athlete_notes' => 'Squat pause solide, bonne vitesse en sortie du trou. Deadlift un peu lent en fin de séance.',
                'status' => SessionFeedback::STATUS_COACH_REPLIED,
                'reply' => 'Bonne séance. On garde le squat tel quel, on allège les accessoires jambes cette semaine.',
                'hour' => 20,
                'minute' => 15,
            ],
            'sarah' => [
                'moment_index' => 0,
                'athlete_notes' => 'Technique au bench en progrès. Vidéo envoyée sur le top set squat.',
                'status' => SessionFeedback::STATUS_COACH_REPLIED,
                'reply' => 'La trajectoire au bench est meilleure. Continue comme ça sur les prochaines séances.',
                'hour' => 18,
                'minute' => 50,
            ],
            'nicolas' => [
                'moment_index' => 0,
                'athlete_notes' => 'Gros deadlift aujourd\'hui, RPE 8.5 sur le top set. Fatigue modérée.',
                'status' => SessionFeedback::STATUS_SUBMITTED,
                'reply' => null,
                'hour' => 21,
                'minute' => 30,
            ],
            'emma' => [
                'moment_index' => 0,
                'athlete_notes' => 'Séance fluide, les trois lifts sont cohérents. Bonne récupération.',
                'status' => SessionFeedback::STATUS_COACH_REPLIED,
                'reply' => 'Parfait, on enchaîne sur la même logique de charges la semaine prochaine.',
                'hour' => 19,
                'minute' => 10,
            ],
            'antoine' => [
                'moment_index' => 0,
                'athlete_notes' => 'Volume géré correctement malgré le poids de corps élevé. Mobilité hanche OK.',
                'status' => SessionFeedback::STATUS_SUBMITTED,
                'reply' => null,
                'hour' => 20,
                'minute' => 45,
            ],
            'julie' => [
                'moment_index' => 0,
                'athlete_notes' => 'Top set bench très propre, pause bien tenue. Squat encore un peu hésitant.',
                'status' => SessionFeedback::STATUS_COACH_REPLIED,
                'reply' => 'Le bench progresse bien. On reste patient sur le squat, la technique avant la charge.',
                'hour' => 17,
                'minute' => 55,
            ],
            'maxime' => [
                'moment_index' => 0,
                'athlete_notes' => 'Bench au top, squat en amélioration. Deadlift stable.',
                'status' => SessionFeedback::STATUS_SUBMITTED,
                'reply' => null,
                'hour' => 20,
                'minute' => 20,
            ],
            'chloe' => [
                'moment_index' => 0,
                'athlete_notes' => 'Genou OK sur le squat pause. Prudence respectée, bonne sensation générale.',
                'status' => SessionFeedback::STATUS_COACH_REPLIED,
                'reply' => 'Content de voir que le genou tient. On ne force rien, progression linéaire.',
                'hour' => 18,
                'minute' => 20,
            ],
            'lucas' => [
                'moment_index' => 0,
                'athlete_notes' => 'Deadlift explosif, meilleure séance du bloc. Bench un peu en retrait.',
                'status' => SessionFeedback::STATUS_SUBMITTED,
                'reply' => null,
                'hour' => 19,
                'minute' => 35,
            ],
            'ines' => [
                'moment_index' => 0,
                'athlete_notes' => 'Bonne forme aujourd\'hui. Récupération optimale, séance complète sans douleur.',
                'status' => SessionFeedback::STATUS_COACH_REPLIED,
                'reply' => 'Excellente constance. On maintient ce rythme jusqu\'à la compétition.',
                'hour' => 19,
                'minute' => 0,
            ],
        ];

        foreach ($athletes as $key => $athlete) {
            $config = $feedbackConfigs[$key] ?? null;
            if ($config === null) {
                continue;
            }

            $moments = $this->completedPlannedSessions($assignments[$key]);
            $momentIndex = $config['moment_index'];

            if (! isset($moments[$momentIndex])) {
                continue;
            }

            $moment = $moments[$momentIndex];

            $feedback = SessionFeedback::query()->create([
                'coach_id' => $coach->id,
                'athlete_id' => $athlete->id,
                'athlete_program_assignment_id' => $assignments[$key]->id,
                'program_training_day_id' => $moment['day']->id,
                'session_date' => $moment['date']->toDateString(),
                'athlete_notes' => $config['athlete_notes'],
                'status' => $config['status'],
                'submitted_at' => $moment['date']->copy()->setTime($config['hour'], $config['minute']),
            ]);

            if ($config['reply'] !== null) {
                FeedbackReplySupport::createCoachReply($feedback, $config['reply']);
            }
        }
    }

    /**
     * @return list<array{date: Carbon, day: ProgramTrainingDay}>
     */
    private function completedPlannedSessions(AthleteProgramAssignment $assignment): array
    {
        $moments = [];
        $today = now()->copy()->startOfDay();

        $assignment->loadMissing('template.weeks.trainingDays');
        $template = $assignment->template;

        if ($template === null) {
            return [];
        }

        foreach ($template->weeks as $week) {
            foreach ($week->trainingDays as $day) {
                $date = $assignment->date_start
                    ->copy()
                    ->startOfDay()
                    ->addWeeks($week->week_number - 1)
                    ->addDays($day->day_number - 1);

                if ($date->gt($today)) {
                    continue;
                }

                $sessionExists = TrainingSession::query()
                    ->where('athlete_id', $assignment->athlete_id)
                    ->whereDate('session_date', $date->toDateString())
                    ->exists();

                if (! $sessionExists) {
                    continue;
                }

                $moments[] = ['date' => $date, 'day' => $day];
            }
        }

        usort(
            $moments,
            static fn (array $left, array $right): int => $right['date']->getTimestamp() <=> $left['date']->getTimestamp(),
        );

        return $moments;
    }

    /**
     * @param  array<string, User>  $athletes
     */
    private function seedMessageThreads(User $coach, array $athletes): void
    {
        $messages = [
            'daily' => [
                ['sender' => 'coach', 'content' => 'La vitesse au squat remonte bien, garde la même routine d’échauffement.'],
                ['sender' => 'athlete', 'content' => 'Oui, meilleure sensation aujourd’hui. Je t’envoie la vidéo du top set.'],
            ],
            'weekly' => [
                ['sender' => 'athlete', 'content' => 'Semaine chargée, j’ai décalé la séance deadlift mais le bench était solide.'],
                ['sender' => 'coach', 'content' => 'Pas de souci pour le décalage. On protège la récup et on garde le bench en priorité.'],
            ],
            'return' => [
                ['sender' => 'coach', 'content' => 'On avance bien. Reste sur cette progressivité, surtout sur les descentes au squat.'],
                ['sender' => 'athlete', 'content' => 'Ça marche, les jambes encaissent mieux que la semaine dernière.'],
            ],
            'thomas' => [
                ['sender' => 'coach', 'content' => 'Bonne semaine Thomas. Le squat pause progresse, on garde le même tempo de montée en charge.'],
                ['sender' => 'athlete', 'content' => 'Merci coach. Le deadlift fatiguait en fin de séance, je pense que c\'est normal à ce stade du bloc.'],
            ],
            'sarah' => [
                ['sender' => 'athlete', 'content' => 'Coach, j\'ai une question sur la pause au bench. Tu préfères 2 ou 3 secondes ?'],
                ['sender' => 'coach', 'content' => 'Reste sur 2 secondes pour l\'instant. On verra en approche de compétition.'],
            ],
            'nicolas' => [
                ['sender' => 'coach', 'content' => 'Nicolas, le pic deadlift est bien calé. Pense à bien gérer ta récup cette semaine.'],
                ['sender' => 'athlete', 'content' => 'Compris. Je dors 8h minimum et j\'allège les accessoires si besoin.'],
            ],
            'emma' => [
                ['sender' => 'athlete', 'content' => 'Séance de ce matin nickel, les trois lifts sont au rendez-vous.'],
                ['sender' => 'coach', 'content' => 'Super Emma. Continue sur cette lancée, on est dans les temps pour la compétition.'],
            ],
            'antoine' => [
                ['sender' => 'coach', 'content' => 'Antoine, pense à tes étirements hanches avant chaque séance squat.'],
                ['sender' => 'athlete', 'content' => 'Oui coach, j\'ai ajouté 10 min de mobilité dans ma routine. Ça aide vraiment.'],
            ],
            'julie' => [
                ['sender' => 'athlete', 'content' => 'Vidéo du bench envoyée. La pause me semble plus stable qu\'avant.'],
                ['sender' => 'coach', 'content' => 'Confirmé, c\'est beaucoup mieux. On monte légèrement la charge semaine prochaine.'],
            ],
            'maxime' => [
                ['sender' => 'coach', 'content' => 'Maxime, le bench est ton point fort. On va pousser un peu plus sur le squat maintenant.'],
                ['sender' => 'athlete', 'content' => 'D\'accord, je suis prêt. Le squat pause commence à bien rentrer.'],
            ],
            'chloe' => [
                ['sender' => 'athlete', 'content' => 'Le genou tient bien cette semaine. Je me sens confiante pour la suite du bloc.'],
                ['sender' => 'coach', 'content' => 'Parfait Chloé. On reste prudent mais on ne freine pas la progression.'],
            ],
            'lucas' => [
                ['sender' => 'coach', 'content' => 'Lucas, ton deadlift est impressionnant pour ta catégorie. On équilibre avec le bench.'],
                ['sender' => 'athlete', 'content' => 'Merci ! Le bench demande plus de travail, je m\'y attelle cette semaine.'],
            ],
            'ines' => [
                ['sender' => 'athlete', 'content' => 'Bonne forme générale, score readiness à 8/10 depuis 3 jours.'],
                ['sender' => 'coach', 'content' => 'Excellent signe. Profite de cette fenêtre pour les séances les plus lourdes.'],
            ],
        ];

        foreach ($messages as $key => $threadMessages) {
            if (! isset($athletes[$key])) {
                continue;
            }

            $thread = MessageThread::query()->firstOrCreate([
                'coach_id' => $coach->id,
                'athlete_id' => $athletes[$key]->id,
            ]);

            if ($thread->messages()->exists()) {
                $thread->touch();

                continue;
            }

            foreach ($threadMessages as $entry) {
                Message::query()->create([
                    'thread_id' => $thread->id,
                    'sender_id' => $entry['sender'] === 'coach' ? $coach->id : $athletes[$key]->id,
                    'content' => $entry['content'],
                ]);
            }

            $thread->touch();
        }
    }

    /**
     * @param  array<string, User>  $athletes
     */
    private function seedReadinessEntries(array $athletes): void
    {
        $scoresByAthlete = [
            'daily' => [7, 8, 6, 7, 9, 8, 7],
            'weekly' => [6, 7, 7, 5, 8, 7, 6],
            'return' => [5, 6, 7, 7, 8, 6, 7],
            'thomas' => [7, 7, 6, 8, 7, 6, 7],
            'sarah' => [8, 8, 7, 9, 8, 7, 8],
            'nicolas' => [6, 5, 6, 7, 6, 5, 6],
            'emma' => [7, 8, 7, 7, 8, 7, 8],
            'antoine' => [6, 6, 7, 6, 7, 6, 7],
            'julie' => [8, 7, 8, 7, 9, 8, 7],
            'maxime' => [7, 6, 7, 7, 6, 7, 7],
            'chloe' => [6, 7, 6, 7, 7, 6, 7],
            'lucas' => [7, 7, 8, 7, 7, 8, 7],
            'ines' => [8, 8, 7, 8, 9, 8, 8],
        ];

        foreach ($scoresByAthlete as $key => $scores) {
            if (! isset($athletes[$key])) {
                continue;
            }

            $athlete = $athletes[$key];

            foreach ($scores as $offset => $score) {
                $sleep = max(1, min(10, $score + (($offset % 3) - 1)));
                $stress = max(1, min(10, $score + (($offset % 2) === 0 ? 0 : -1)));
                $motivation = max(1, min(10, $score + (($offset % 4) - 2)));

                AthleteReadinessEntry::query()->create([
                    'athlete_id' => $athlete->id,
                    'entry_date' => now()->copy()->subDays(6 - $offset)->toDateString(),
                    'sleep_score' => $sleep,
                    'stress_score' => $stress,
                    'motivation_score' => $motivation,
                    'score' => AthleteReadinessEntry::computeScore($sleep, $stress, $motivation),
                ]);
            }
        }
    }

    private function roundToNearest(float $value, float $step): float
    {
        return round($value / $step) * $step;
    }
}
