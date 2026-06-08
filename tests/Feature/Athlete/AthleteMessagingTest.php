<?php

namespace Tests\Feature\Athlete;

use App\Models\AthleteProfile;
use App\Models\AthleteProgramAssignment;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AthleteMessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_can_access_messaging_with_coach_thread(): void
    {
        [$coach, $athlete] = $this->seedAthleteWithCoach();

        $thread = MessageThread::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);

        Message::query()->create([
            'thread_id' => $thread->id,
            'sender_id' => $coach->id,
            'content' => 'Prêt pour la séance ?',
        ]);

        $this->actingAs($athlete)
            ->get('/messaging')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MessagingPage')
                ->where('role', 'athlete')
                ->has('threads', 1)
                ->where('activeThread.id', $thread->id)
                ->where('activeThread.coach.name', 'Coach')
                ->has('messages', 1)
            );
    }

    public function test_athlete_can_send_message_in_thread(): void
    {
        [$coach, $athlete] = $this->seedAthleteWithCoach();

        $thread = MessageThread::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);

        $this->actingAs($athlete)
            ->post("/coach/threads/{$thread->id}/messages", [
                'content' => 'Oui coach, je suis prêt.',
            ])
            ->assertRedirect(route('messaging', ['thread' => $thread->id]));

        $this->assertDatabaseHas('messages', [
            'thread_id' => $thread->id,
            'sender_id' => $athlete->id,
            'content' => 'Oui coach, je suis prêt.',
        ]);
    }

    public function test_messaging_inbox_is_shared_for_athlete(): void
    {
        [$coach, $athlete] = $this->seedAthleteWithCoach();

        $thread = MessageThread::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);

        Message::query()->create([
            'thread_id' => $thread->id,
            'sender_id' => $coach->id,
            'content' => 'Message non lu',
        ]);

        $this->actingAs($athlete)
            ->get('/athlete/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('messagingInbox.thread_id', $thread->id)
                ->where('messagingInbox.unread_count', 1)
            );
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function seedAthleteWithCoach(): array
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
            'day_number' => 3,
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
