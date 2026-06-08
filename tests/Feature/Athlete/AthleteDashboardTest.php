<?php

namespace Tests\Feature\Athlete;

use App\Models\AthleteProfile;
use App\Models\AthleteProgramAssignment;
use App\Models\Competition;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\PersonalRecord;
use App\Models\ProgramTemplate;
use App\Models\TrainingSession;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AthleteDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_can_access_dashboard_with_today_session(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 09:00:00'));

        [$coach, $athlete] = $this->seedAthleteWithProgram(dayNumber: 3);

        $this->actingAs($athlete)
            ->get('/athlete/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('AthleteDashboardPage')
                ->where('athleteId', $athlete->id)
                ->where('todaySession.status', 'session')
                ->where('todaySession.date', '2026-05-20')
                ->has('todaySession.session')
            );

        Carbon::setTestNow();
    }

    public function test_athlete_dashboard_shows_rest_day_when_no_session_today(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19 09:00:00'));

        [$coach, $athlete] = $this->seedAthleteWithProgram(dayNumber: 3);

        $this->actingAs($athlete)
            ->get('/athlete/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('todaySession.status', 'rest')
                ->where('todaySession.next_session_date', '2026-05-20')
            );

        Carbon::setTestNow();
    }

    public function test_athlete_dashboard_shows_no_program_without_assignment(): void
    {
        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => 'solo@example.com',
            'password' => bcrypt('password'),
            'role' => 'athlete',
            'initial_setup_completed_at' => now(),
        ]);

        $this->actingAs($athlete)
            ->get('/athlete/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('todaySession.status', 'no_program')
            );
    }

    public function test_dashboard_includes_competition_countdown_and_block_progress(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 09:00:00'));

        [$coach, $athlete] = $this->seedAthleteWithProgram(dayNumber: 3);

        Competition::query()->create([
            'athlete_id' => $athlete->id,
            'name' => 'Open de Lyon',
            'competition_date' => '2026-06-01',
            'goal' => '500 kg',
            'location' => 'Lyon',
        ]);

        $this->actingAs($athlete)
            ->get('/athlete/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('nextCompetition.name', 'Open de Lyon')
                ->where('nextCompetition.days_until', 12)
                ->has('programBlock')
                ->where('blockProgress.week_current', 1)
                ->where('blockProgress.week_count', 1)
                ->where('blockProgress.block_type', ProgramWeek::BLOCK_VOLUME)
                ->where('feedbackDueToday', true)
            );

        Carbon::setTestNow();
    }

    public function test_athlete_can_access_program_page_with_block_data(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 09:00:00'));

        [, $athlete] = $this->seedAthleteWithProgram(dayNumber: 3);

        $this->actingAs($athlete)
            ->get('/athlete/program')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('AthleteProgramPage')
                ->has('programBlock')
                ->where('blockProgress.week_current', 1)
            );

        Carbon::setTestNow();
    }

    public function test_coach_is_redirected_from_athlete_program(): void
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach2@example.com',
            'password' => bcrypt('password'),
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
        ]);

        $this->actingAs($coach)
            ->get('/athlete/program')
            ->assertRedirect(route('dashboard'));
    }

    public function test_dashboard_includes_enriched_athlete_payload(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 09:00:00'));

        [$coach, $athlete] = $this->seedAthleteWithProgram(dayNumber: 3);

        PersonalRecord::query()->create([
            'athlete_id' => $athlete->id,
            'squat' => 150,
            'bench' => 90,
            'deadlift' => 180,
            'reference_date' => '2026-05-01',
        ]);

        TrainingSession::query()->create([
            'athlete_id' => $athlete->id,
            'session_date' => '2026-05-20',
            'main_lift' => 'squat',
            'session_label' => 'Force',
            'items' => [],
            'squat' => 0,
            'bench' => 0,
            'deadlift' => 0,
        ]);

        $thread = MessageThread::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);

        Message::query()->create([
            'thread_id' => $thread->id,
            'sender_id' => $coach->id,
            'content' => 'Salut !',
        ]);

        $this->actingAs($athlete)
            ->get('/athlete/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('todayLoggedSession')
                ->where('todayLoggedSession.session_date', '2026-05-20')
                ->has('oneRm')
                ->has('recentFeedbacks')
                ->has('feedbackSummary')
                ->has('coachThread')
                ->where('coachThread.coach_name', 'Coach')
                ->where('coachThread.unread_count', 1)
                ->has('personalRecords', 1)
            );

        Carbon::setTestNow();
    }

    public function test_athlete_can_store_own_pr_from_dashboard_route(): void
    {
        [$coach, $athlete] = $this->seedAthleteWithProgram(dayNumber: 3);

        $this->actingAs($athlete)
            ->post("/athletes/{$athlete->id}/prs", [
                'squat' => 160,
                'bench' => 100,
                'deadlift' => 190,
                'reference_date' => '2026-05-20',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('personal_records', [
            'athlete_id' => $athlete->id,
            'squat' => 160,
            'bench' => 100,
            'deadlift' => 190,
        ]);
    }

    public function test_athlete_can_store_training_session(): void
    {
        [, $athlete] = $this->seedAthleteWithProgram(dayNumber: 3);

        $this->actingAs($athlete)
            ->post("/athletes/{$athlete->id}/training-sessions", [
                'session_date' => '2026-05-20',
                'main_lift' => 'squat',
                'session_label' => 'Force',
                'items' => [],
                'blocks' => [],
                'notes' => null,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('training_sessions', [
            'athlete_id' => $athlete->id,
            'session_date' => '2026-05-20',
        ]);
    }

    public function test_coach_is_redirected_from_athlete_dashboard(): void
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach@example.com',
            'password' => bcrypt('password'),
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
        ]);

        $this->actingAs($coach)
            ->get('/athlete/dashboard')
            ->assertRedirect(route('dashboard'));
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function seedAthleteWithProgram(int $dayNumber): array
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach@example.com',
            'password' => bcrypt('password'),
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
        ]);

        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => 'athlete@example.com',
            'password' => bcrypt('password'),
            'role' => 'athlete',
            'initial_setup_completed_at' => now(),
        ]);

        $coach->athletes()->attach($athlete->id, ['status' => 'active']);

        AthleteProfile::query()->create([
            'user_id' => $athlete->id,
            'feedback_frequency' => AthleteProfile::FREQUENCY_DAILY,
        ]);

        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Bloc test',
            'level' => 'intermediate',
        ]);

        $week = ProgramWeek::query()->create([
            'template_id' => $template->id,
            'week_number' => 1,
            'block_type' => ProgramWeek::BLOCK_VOLUME,
        ]);

        ProgramTrainingDay::query()->create([
            'week_id' => $week->id,
            'day_number' => $dayNumber,
            'main_lift' => ProgramTrainingDay::LIFT_SQUAT,
            'session_label' => 'Force',
        ]);

        AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-05-18',
            'status' => 'active',
        ]);

        return [$coach, $athlete];
    }
}
