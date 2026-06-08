<?php

namespace Tests\Feature\Athlete;

use App\Models\AthleteReadinessEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AthleteReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_athlete_can_submit_readiness_with_three_scores(): void
    {
        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => 'athlete@example.com',
            'password' => bcrypt('password'),
            'role' => 'athlete',
            'initial_setup_completed_at' => now(),
        ]);

        $this->actingAs($athlete)
            ->post("/athletes/{$athlete->id}/readiness", [
                'sleep_score' => 8,
                'stress_score' => 6,
                'motivation_score' => 7,
                'notes' => 'Bien dormi',
            ])
            ->assertRedirect(route('athlete.dashboard'));

        $entry = AthleteReadinessEntry::query()->where('athlete_id', $athlete->id)->first();
        $this->assertNotNull($entry);
        $this->assertSame(8, $entry->sleep_score);
        $this->assertSame(6, $entry->stress_score);
        $this->assertSame(7, $entry->motivation_score);
        $this->assertSame(7, $entry->score);
        $this->assertSame('Bien dormi', $entry->notes);
    }

    public function test_athlete_cannot_submit_readiness_for_another_athlete(): void
    {
        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => 'athlete@example.com',
            'password' => bcrypt('password'),
            'role' => 'athlete',
            'initial_setup_completed_at' => now(),
        ]);

        $other = User::query()->create([
            'name' => 'Autre',
            'email' => 'autre@example.com',
            'password' => bcrypt('password'),
            'role' => 'athlete',
            'initial_setup_completed_at' => now(),
        ]);

        $this->actingAs($athlete)
            ->post("/athletes/{$other->id}/readiness", [
                'sleep_score' => 7,
                'stress_score' => 7,
                'motivation_score' => 7,
            ])
            ->assertForbidden();
    }

    public function test_readiness_scores_must_be_between_one_and_ten(): void
    {
        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => 'athlete@example.com',
            'password' => bcrypt('password'),
            'role' => 'athlete',
            'initial_setup_completed_at' => now(),
        ]);

        $this->actingAs($athlete)
            ->post("/athletes/{$athlete->id}/readiness", [
                'sleep_score' => 0,
                'stress_score' => 7,
                'motivation_score' => 11,
            ])
            ->assertSessionHasErrors(['sleep_score', 'motivation_score']);
    }
}
