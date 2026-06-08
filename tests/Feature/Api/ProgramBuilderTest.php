<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProgramBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_create_program_template(): void
    {
        $this->seed(\Database\Seeders\ExerciseLibrarySeeder::class);

        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach@builder.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        Sanctum::actingAs($coach);

        $this->postJson('/api/v1/program-templates', [
            'name' => 'Cycle force',
            'goal' => 'Peak',
            'level' => 'advanced',
            'weeks' => [
                [
                    'week_number' => 1,
                    'block_type' => 'volume',
                    'days' => [
                        [
                            'day_number' => 1,
                            'main_lift' => 'squat',
                            'topset' => [
                                'exercise_name' => 'Squat classique',
                                'sets' => 1,
                                'reps' => 3,
                                'load' => 160,
                            ],
                            'backoff' => [
                                'exercise_name' => 'Squat classique',
                                'sets' => 3,
                                'reps' => 6,
                                'load' => 130,
                            ],
                            'accessories' => [
                                [
                                    'exercise_name' => 'Leg press',
                                    'sets' => 3,
                                    'reps' => 10,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ])->assertCreated()->assertJsonPath('name', 'Cycle force');
    }
}
