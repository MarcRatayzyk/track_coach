<?php

namespace Tests\Feature\Coach;

use App\Models\Message;
use App\Models\MessageThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingUnreadTest extends TestCase
{
    use RefreshDatabase;

    public function test_unread_threads_are_listed_first_and_marked_read_when_opened(): void
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach-unread@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $athleteA = User::query()->create([
            'name' => 'Athlete A',
            'email' => 'athlete-a@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);

        $athleteB = User::query()->create([
            'name' => 'Athlete B',
            'email' => 'athlete-b@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);

        $coach->athletes()->attach($athleteA->id, ['status' => 'active']);
        $coach->athletes()->attach($athleteB->id, ['status' => 'active']);

        $recentThread = MessageThread::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athleteA->id,
            'updated_at' => now(),
        ]);

        $unreadThread = MessageThread::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athleteB->id,
            'updated_at' => now()->subDay(),
        ]);

        Message::query()->create([
            'thread_id' => $unreadThread->id,
            'sender_id' => $athleteB->id,
            'content' => 'Coach, question sur la séance.',
        ]);

        $this->actingAs($coach)
            ->get('/messaging')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MessagingPage')
                ->has('threads', 2)
                ->where('threads.0.id', $unreadThread->id)
                ->where('threads.0.unread_messages_count', 1)
                ->where('threads.1.id', $recentThread->id)
                ->where('threads.1.unread_messages_count', 0));

        $this->actingAs($coach)
            ->get('/messaging?thread='.$unreadThread->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('threads.0.unread_messages_count', 0));

        $this->assertDatabaseMissing('messages', [
            'thread_id' => $unreadThread->id,
            'read_at' => null,
        ]);
    }

    public function test_dashboard_lists_unread_conversations_first(): void
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach-dash-unread@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => 'athlete-dash@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);

        $coach->athletes()->attach($athlete->id, ['status' => 'active']);

        $thread = MessageThread::query()->create([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);

        Message::query()->create([
            'thread_id' => $thread->id,
            'sender_id' => $athlete->id,
            'content' => 'Retour séance.',
        ]);

        $this->actingAs($coach)
            ->get('/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('DashboardPage')
                ->where('recentThreads.0.unread_messages_count', 1));
    }
}
