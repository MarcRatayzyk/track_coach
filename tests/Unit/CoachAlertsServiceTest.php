<?php

namespace Tests\Unit;

use App\Models\AthleteProgramAssignment;
use App\Models\Competition;
use App\Models\DashboardTask;
use App\Models\ProgramDayExercise;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\SessionFeedback;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\CoachAlertsService;
use App\Support\MatchPlanData;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoachAlertsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_ending_and_competition_alerts_are_returned(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-18 10:00:00'));

        [$coach, $athlete, $template] = $this->seedCoachAthlete();

        AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-05-01',
            'date_end' => '2026-05-22',
            'status' => 'active',
        ]);

        Competition::query()->create([
            'athlete_id' => $athlete->id,
            'name' => 'Open Régional',
            'competition_date' => '2026-06-01',
            'goal' => '500 kg',
        ]);

        $alerts = app(CoachAlertsService::class)->forCoach($coach);

        $types = collect($alerts)->pluck('type');

        $this->assertTrue($types->contains('block_ending'));
        $this->assertTrue($types->contains('competition_soon'));

        Carbon::setTestNow();
    }

    public function test_competition_alert_is_hidden_when_match_plan_is_defined(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-18 10:00:00'));

        [$coach, $athlete] = array_slice($this->seedCoachAthlete(), 0, 2);

        $matchPlanData = MatchPlanData::normalize([
            'mode' => 'structured',
            'scenarios' => [
                [
                    'name' => 'Plan principal',
                    'lifts' => [
                        'squat' => ['attempt1' => 100, 'attempt2' => 105, 'attempt3' => 110],
                        'bench' => ['attempt1' => 80, 'attempt2' => 85, 'attempt3' => 90],
                        'deadlift' => ['attempt1' => 120, 'attempt2' => 125, 'attempt3' => 130],
                    ],
                ],
            ],
        ]);

        Competition::query()->create([
            'athlete_id' => $athlete->id,
            'name' => 'Open Régional',
            'competition_date' => '2026-06-01',
            'match_plan_data' => $matchPlanData,
            'match_plan' => MatchPlanData::toText($matchPlanData),
        ]);

        $alerts = app(CoachAlertsService::class)->forCoach($coach);

        $this->assertFalse(
            collect($alerts)->contains(fn (array $alert) => $alert['type'] === 'competition_soon'),
        );

        Carbon::setTestNow();
    }

    public function test_adherence_drop_alert_when_recent_week_is_weaker(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-25 10:00:00')); // Sunday

        [$coach, $athlete, $template] = $this->seedCoachAthlete();

        $week = ProgramWeek::query()->create([
            'template_id' => $template->id,
            'week_number' => 1,
            'block_type' => ProgramWeek::BLOCK_VOLUME,
        ]);

        foreach ([1, 3, 5] as $dayNumber) {
            $day = ProgramTrainingDay::query()->create([
                'week_id' => $week->id,
                'day_number' => $dayNumber,
                'main_lift' => ProgramTrainingDay::LIFT_SQUAT,
            ]);

            ProgramDayExercise::query()->create([
                'training_day_id' => $day->id,
                'block_index' => 0,
                'section' => ProgramDayExercise::SECTION_TOPSET,
                'exercise_name' => 'Squat',
                'sets' => 3,
                'reps' => 5,
                'load' => 100,
                'sort_order' => 0,
            ]);
        }

        AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-05-05',
            'status' => 'active',
        ]);

        $matchingItem = [
            'exercise_name' => 'Squat',
            'sets' => 3,
            'reps' => 5,
            'load' => 100,
            'lift' => 'squat',
        ];

        foreach (['2026-05-12', '2026-05-14', '2026-05-16'] as $sessionDate) {
            TrainingSession::query()->create([
                'athlete_id' => $athlete->id,
                'session_date' => $sessionDate,
                'main_lift' => 'squat',
                'items' => [$matchingItem],
            ]);
        }

        TrainingSession::query()->create([
            'athlete_id' => $athlete->id,
            'session_date' => '2026-05-23',
            'main_lift' => 'squat',
            'items' => [
                [
                    'exercise_name' => 'Squat',
                    'sets' => 3,
                    'reps' => 5,
                    'load' => 80,
                    'lift' => 'squat',
                ],
            ],
        ]);

        $alerts = app(CoachAlertsService::class)->forCoach($coach);

        $this->assertTrue(
            collect($alerts)->contains(fn (array $alert) => $alert['type'] === 'adherence_drop'),
        );

        Carbon::setTestNow();
    }

    public function test_no_feedback_reply_alert_when_coach_has_not_replied(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 10:00:00'));

        [$coach, $athlete] = array_slice($this->seedCoachAthlete(), 0, 2);

        DashboardTask::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'type' => DashboardTask::TYPE_FEEDBACK_SESSION,
            'session_date' => '2026-05-18',
            'status' => 'pending',
        ]);

        SessionFeedback::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'session_date' => '2026-05-18',
            'status' => SessionFeedback::STATUS_SUBMITTED,
            'submitted_at' => '2026-05-19 18:00:00',
        ]);

        $alerts = app(CoachAlertsService::class)->forCoach($coach);

        $this->assertFalse(
            collect($alerts)->contains(fn (array $alert) => str_starts_with($alert['type'], 'feedback_')),
        );

        Carbon::setTestNow();
    }

    public function test_at_most_two_alerts_per_type(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-18 10:00:00'));

        [$coach, $athlete] = array_slice($this->seedCoachAthlete(), 0, 2);

        foreach (['2026-06-01', '2026-06-08', '2026-06-15'] as $date) {
            Competition::query()->create([
                'athlete_id' => $athlete->id,
                'name' => "Meet {$date}",
                'competition_date' => $date,
                'goal' => '500 kg',
            ]);
        }

        $alerts = app(CoachAlertsService::class)->forCoach($coach);
        $competitionAlerts = collect($alerts)->where('type', 'competition_soon');

        $this->assertCount(2, $competitionAlerts);

        Carbon::setTestNow();
    }

    /**
     * @return array{0: User, 1: User, 2: ProgramTemplate}
     */
    private function seedCoachAthlete(): array
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => uniqid('coach-alerts-', true).'@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => uniqid('athlete-alerts-', true).'@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);

        $coach->athletes()->attach($athlete->id, ['status' => 'active']);

        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Bloc test',
        ]);

        return [$coach, $athlete, $template];
    }
}
