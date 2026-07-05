<?php

namespace Tests\Feature\Coach;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_filter_exercise_library(): void
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach@exercises.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
            'initial_setup_completed_at' => now(),
            'email_verified_at' => now(),
        ]);

        Exercise::query()->create([
            'name' => 'Squat',
            'slug' => 'squat',
            'lift' => Exercise::LIFT_SQUAT,
            'category' => Exercise::CATEGORY_MAIN_LIFT,
            'equipment' => 'barbell',
        ]);

        Exercise::query()->create([
            'name' => 'Skull crusher',
            'slug' => 'skull-crusher',
            'lift' => Exercise::LIFT_BENCH,
            'category' => Exercise::CATEGORY_ACCESSORY,
            'equipment' => 'barbell',
        ]);

        $this->actingAs($coach)
            ->getJson('/coach/exercises?category=accessory&lift=bench')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Skull crusher');
    }
}
