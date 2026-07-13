<?php

namespace Tests\Feature;

use App\Models\AthleteProfile;
use App\Models\AthleteProgramAssignment;
use App\Models\DashboardTask;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\SessionFeedback;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SessionFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_athlete_can_submit_feedback_and_coach_can_reply(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 14:00:00'));

        [$coach, $athlete, $assignment, $trainingDay] = $this->seedProgramSession();

        $task = DashboardTask::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'type' => DashboardTask::TYPE_FEEDBACK_SESSION,
            'session_date' => '2026-05-20',
            'status' => 'pending',
        ]);

        $this->actingAs($athlete)
            ->post('/feedbacks', [
                'session_date' => '2026-05-20',
                'athlete_notes' => 'Bonne séance, squat difficile.',
                'videos' => [
                    UploadedFile::fake()->create('lift.mp4', 512, 'video/mp4'),
                ],
            ])
            ->assertRedirect();

        $feedback = SessionFeedback::query()->first();
        $this->assertNotNull($feedback);
        $this->assertSame('submitted', $feedback->status);
        $this->assertSame($trainingDay->id, $feedback->program_training_day_id);
        $task->refresh();
        $this->assertSame($feedback->id, $task->session_feedback_id);

        $this->actingAs($coach)
            ->post("/feedbacks/{$feedback->id}/reply", [
                'body' => 'Bien joué, descends la charge la prochaine fois.',
                'audio_files' => [
                    UploadedFile::fake()->create('note.mp3', 100, 'audio/mpeg'),
                ],
            ])
            ->assertRedirect();

        $feedback->refresh();
        $this->assertSame(SessionFeedback::STATUS_COACH_REPLIED, $feedback->status);
        $this->assertNotNull($feedback->reply);
        $this->assertSame('done', $task->fresh()->status);

        Carbon::setTestNow();
    }

    public function test_athlete_can_submit_text_only_feedback(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 14:00:00'));

        [$coach, $athlete, , $trainingDay] = $this->seedProgramSession();

        $this->actingAs($athlete)
            ->post('/feedbacks', [
                'session_date' => '2026-05-20',
                'athlete_notes' => 'Séance terminée sans vidéo.',
            ])
            ->assertRedirect();

        $feedback = SessionFeedback::query()->first();
        $this->assertNotNull($feedback);
        $this->assertSame('Séance terminée sans vidéo.', $feedback->athlete_notes);
        $this->assertSame(0, $feedback->athleteVideos()->count());

        Carbon::setTestNow();
    }

    public function test_athlete_cannot_submit_empty_feedback(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 14:00:00'));

        [, $athlete] = $this->seedProgramSession();

        $this->actingAs($athlete)
            ->post('/feedbacks', [
                'session_date' => '2026-05-20',
                'athlete_notes' => '   ',
            ])
            ->assertSessionHasErrors('athlete_notes');

        $this->assertDatabaseCount('session_feedbacks', 0);

        Carbon::setTestNow();
    }

    public function test_cannot_submit_without_program_session_on_date(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-19 14:00:00'));

        [$coach, $athlete] = $this->seedProgramSession(skipTrainingDay: true);

        $this->actingAs($athlete)
            ->post('/feedbacks', [
                'session_date' => '2026-05-19',
                'athlete_notes' => 'Test',
                'videos' => [
                    UploadedFile::fake()->create('lift.mp4', 512, 'video/mp4'),
                ],
            ])
            ->assertSessionHasErrors('session_date');

        Carbon::setTestNow();
    }

    public function test_other_athlete_cannot_view_feedback(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 14:00:00'));

        [$coach, $athlete, , $trainingDay] = $this->seedProgramSession();

        $feedback = SessionFeedback::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'athlete_program_assignment_id' => AthleteProgramAssignment::query()->first()->id,
            'program_training_day_id' => $trainingDay->id,
            'session_date' => '2026-05-20',
            'athlete_notes' => 'Privé',
            'status' => SessionFeedback::STATUS_SUBMITTED,
            'submitted_at' => now(),
        ]);

        $other = User::query()->create([
            'name' => 'Autre',
            'email' => 'autre@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
            'initial_setup_completed_at' => now(),
        ]);

        $this->actingAs($other)
            ->get("/feedbacks/{$feedback->id}")
            ->assertForbidden();

        Carbon::setTestNow();
    }

    /**
     * @return array{0: User, 1: User, 2: AthleteProgramAssignment, 3: ProgramTrainingDay}
     */
    private function seedProgramSession(bool $skipTrainingDay = false): array
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => uniqid('coach-', true).'@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
        ]);

        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => uniqid('athlete-', true).'@test.dev',
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
            'name' => 'Test',
            'level' => 'intermediate',
        ]);

        $week = ProgramWeek::query()->create([
            'template_id' => $template->id,
            'week_number' => 1,
            'block_type' => ProgramWeek::BLOCK_VOLUME,
        ]);

        $trainingDay = ProgramTrainingDay::query()->create([
            'week_id' => $week->id,
            'day_number' => 3,
            'main_lift' => ProgramTrainingDay::LIFT_SQUAT,
            'session_label' => 'Force',
        ]);

        if ($skipTrainingDay) {
            $trainingDay->delete();
            $trainingDay = ProgramTrainingDay::query()->create([
                'week_id' => $week->id,
                'day_number' => 5,
                'main_lift' => ProgramTrainingDay::LIFT_BENCH,
            ]);
        }

        $assignment = AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-05-18',
            'status' => 'active',
        ]);

        return [$coach, $athlete, $assignment, $trainingDay];
    }
}
