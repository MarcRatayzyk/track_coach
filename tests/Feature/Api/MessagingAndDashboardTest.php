<?php

namespace Tests\Feature\Api;

use App\Models\DashboardTask;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MessagingAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_message_and_dashboard_endpoint(): void
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach@msg.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => 'athlete@msg.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);

        $coach->athletes()->attach($athlete->id, ['status' => 'active']);

        Sanctum::actingAs($coach);

        $thread = MessageThread::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);

        $this->postJson('/api/v1/threads/'.$thread->id.'/messages', [
            'content' => 'Bonne séance, maintiens le tempo.',
        ])->assertCreated();

        DashboardTask::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
            'type' => 'feedback_session',
            'session_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $this->getJson('/api/v1/dashboard/coach')
            ->assertOk()
            ->assertJsonStructure([
                'feedback' => [
                    'daily',
                    'weekly',
                    'week_start',
                    'week_end',
                    'today',
                ],
            ]);
    }
}
