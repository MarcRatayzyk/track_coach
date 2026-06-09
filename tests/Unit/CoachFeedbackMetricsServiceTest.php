<?php

namespace Tests\Unit;

use App\Actions\SyncCoachFeedbackExpectations;
use App\Models\AthleteProfile;
use App\Models\AthleteProgramAssignment;
use App\Models\DashboardTask;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\ProgramTemplate;
use App\Models\SessionFeedback;
use App\Models\User;
use App\Services\CoachFeedbackMetricsService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoachFeedbackMetricsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_task_created_when_session_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-18 10:00:00')); // Monday

        [$coach, $athlete, $template] = $this->seedCoachAthleteTemplate(AthleteProfile::FREQUENCY_DAILY);

        $week = ProgramWeek::query()->create([
            'template_id' => $template->id,
            'week_number' => 1,
            'block_type' => ProgramWeek::BLOCK_VOLUME,
        ]);

        ProgramTrainingDay::query()->create([
            'week_id' => $week->id,
            'day_number' => 1,
            'main_lift' => ProgramTrainingDay::LIFT_SQUAT,
        ]);

        AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-05-18',
            'status' => 'active',
        ]);

        app(SyncCoachFeedbackExpectations::class)->execute($coach);

        $this->assertDatabaseHas('dashboard_tasks', [
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'type' => DashboardTask::TYPE_FEEDBACK_SESSION,
            'session_date' => '2026-05-18',
            'status' => 'pending',
        ]);
    }

    public function test_overdue_daily_task_remains_pending_next_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19 10:00:00')); // Tuesday

        [$coach, $athlete] = $this->seedCoachAthleteTemplate(AthleteProfile::FREQUENCY_DAILY);

        DashboardTask::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'type' => DashboardTask::TYPE_FEEDBACK_SESSION,
            'session_date' => '2026-05-18',
            'due_at' => '2026-05-18 23:59:59',
            'status' => 'pending',
        ]);

        $metrics = app(CoachFeedbackMetricsService::class)->forCoach($coach);

        $this->assertSame(1, $metrics['daily']['overdue']);
        $this->assertCount(1, $metrics['daily']['pending_tasks']);
        $this->assertSame('2026-05-18', $metrics['daily']['pending_tasks']->first()['session_date']);

        Carbon::setTestNow();
    }

    public function test_weekly_athlete_gets_one_task_per_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-18 10:00:00'));

        [$coach, $athlete, $template] = $this->seedCoachAthleteTemplate(AthleteProfile::FREQUENCY_WEEKLY);

        AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-05-18',
            'status' => 'active',
        ]);

        app(SyncCoachFeedbackExpectations::class)->execute($coach);
        app(SyncCoachFeedbackExpectations::class)->execute($coach);

        $this->assertSame(
            1,
            DashboardTask::query()
                ->where('athlete_id', $athlete->id)
                ->whereNotNull('period_week_start')
                ->count()
        );

        Carbon::setTestNow();
    }

    public function test_weekly_received_ignores_daily_session_feedbacks(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-09 10:00:00'));

        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => uniqid('coach-', true).'@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $dailyAthlete = User::query()->create([
            'name' => 'Daily Athlete',
            'email' => uniqid('daily-', true).'@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);

        $weeklyAthlete = User::query()->create([
            'name' => 'Weekly Athlete',
            'email' => uniqid('weekly-', true).'@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);

        $coach->athletes()->attach($dailyAthlete->id, ['status' => 'active']);
        $coach->athletes()->attach($weeklyAthlete->id, ['status' => 'active']);

        AthleteProfile::query()->create([
            'user_id' => $dailyAthlete->id,
            'feedback_frequency' => AthleteProfile::FREQUENCY_DAILY,
        ]);

        AthleteProfile::query()->create([
            'user_id' => $weeklyAthlete->id,
            'feedback_frequency' => AthleteProfile::FREQUENCY_WEEKLY,
        ]);

        DashboardTask::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $weeklyAthlete->id,
            'type' => DashboardTask::TYPE_FEEDBACK_SESSION,
            'period_week_start' => '2026-06-08',
            'due_at' => '2026-06-14 23:59:59',
            'status' => 'pending',
        ]);

        SessionFeedback::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $dailyAthlete->id,
            'session_date' => '2026-06-09',
            'status' => 'submitted',
            'submitted_at' => '2026-06-09 12:00:00',
        ]);

        $metrics = app(CoachFeedbackMetricsService::class)->forCoach($coach);

        $this->assertSame(0, $metrics['weekly']['received_week']);

        Carbon::setTestNow();
    }

    public function test_weekly_pending_task_links_existing_week_feedback(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-31 10:00:00'));

        [$coach, $athlete] = $this->seedCoachAthleteTemplate(AthleteProfile::FREQUENCY_WEEKLY);

        $task = DashboardTask::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'type' => DashboardTask::TYPE_FEEDBACK_SESSION,
            'period_week_start' => '2026-05-25',
            'due_at' => '2026-05-31 23:59:59',
            'status' => 'pending',
        ]);

        $feedback = SessionFeedback::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'session_date' => '2026-05-28',
            'status' => 'submitted',
            'submitted_at' => '2026-05-28 20:00:00',
        ]);

        $metrics = app(CoachFeedbackMetricsService::class)->forCoach($coach);

        $task->refresh();
        $this->assertSame($feedback->id, $task->session_feedback_id);

        $pending = $metrics['weekly']['pending_tasks']->first();
        $this->assertTrue($pending['has_submission']);
        $this->assertSame($feedback->id, $pending['session_feedback_id']);

        Carbon::setTestNow();
    }

    /**
     * @return array{0: User, 1: User, 2: ProgramTemplate}
     */
    private function seedCoachAthleteTemplate(string $frequency): array
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => uniqid('coach-', true).'@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => uniqid('athlete-', true).'@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);

        $coach->athletes()->attach($athlete->id, ['status' => 'active']);

        AthleteProfile::query()->create([
            'user_id' => $athlete->id,
            'feedback_frequency' => $frequency,
        ]);

        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Test',
            'level' => 'intermediate',
        ]);

        return [$coach, $athlete, $template];
    }
}
