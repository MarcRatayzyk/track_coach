<?php

namespace Tests\Feature\Coach;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramBlockBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_coach_can_create_program_block(): void
    {
        [$coach, $athlete] = $this->seedCoachAthlete();

        $this->actingAs($coach)
            ->post('/coach/program-blocks', [
                'athlete_id' => $athlete->id,
                'name' => 'Bloc prépa',
                'week_count' => 3,
                'date_start' => '2026-06-02',
            ])
            ->assertRedirect();

        $assignment = AthleteProgramAssignment::query()->first();
        $this->assertNotNull($assignment);
        $this->assertSame('draft', $assignment->status);
        $this->assertSame('Bloc prépa', $assignment->template->name);
        $this->assertSame(3, $assignment->template->weeks()->count());
        $this->assertSame('2026-06-22', $assignment->date_end->toDateString());
    }

    public function test_coach_can_upsert_and_clear_session(): void
    {
        [$coach, $athlete] = $this->seedCoachAthlete();

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

        $assignment = AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-06-02',
            'status' => 'active',
        ]);

        $this->actingAs($coach)
            ->put("/coach/program-blocks/{$assignment->id}/sessions", [
                'week_number' => 1,
                'weekday' => 1,
                'blocks' => [
                    [
                        'lift' => 'squat',
                        'topset' => [
                            'exercise_name' => 'Squat classique',
                            'sets' => 1,
                            'reps' => 3,
                            'load' => 160,
                        ],
                        'accessories' => [],
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('program_training_days', [
            'week_id' => $week->id,
            'day_number' => 1,
            'main_lift' => 'squat',
        ]);

        $this->actingAs($coach)
            ->delete("/coach/program-blocks/{$assignment->id}/sessions", [
                'week_number' => 1,
                'weekday' => 1,
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('program_training_days', [
            'week_id' => $week->id,
            'day_number' => 1,
        ]);
    }

    public function test_coach_can_assign_program_block_to_athlete(): void
    {
        [$coach, $athlete] = $this->seedCoachAthlete();

        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Bloc assign',
            'level' => 'intermediate',
        ]);

        $assignment = AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-06-02',
            'status' => 'draft',
        ]);

        $this->actingAs($coach)
            ->post("/coach/program-blocks/{$assignment->id}/assign")
            ->assertRedirect(route('program.builder', ['assignment' => $assignment->id]));

        $assignment->refresh();
        $this->assertSame('active', $assignment->status);
    }

    public function test_coach_can_delete_program_block(): void
    {
        [$coach, $athlete] = $this->seedCoachAthlete();

        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Bloc à supprimer',
            'level' => 'intermediate',
        ]);

        $assignment = AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-06-02',
            'status' => 'active',
        ]);

        $this->actingAs($coach)
            ->delete("/coach/program-blocks/{$assignment->id}")
            ->assertRedirect(route('program.builder'));

        $this->assertDatabaseMissing('athlete_program_assignments', ['id' => $assignment->id]);
        $this->assertDatabaseMissing('program_templates', ['id' => $template->id]);
    }

    public function test_program_builder_loads_active_block(): void
    {
        [$coach, $athlete] = $this->seedCoachAthlete();

        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Bloc calendrier',
            'level' => 'intermediate',
        ]);

        ProgramWeek::query()->create([
            'template_id' => $template->id,
            'week_number' => 1,
            'block_type' => ProgramWeek::BLOCK_VOLUME,
        ]);

        $assignment = AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-06-02',
            'status' => 'active',
        ]);

        $week = $template->weeks()->first();
        ProgramTrainingDay::query()->create([
            'week_id' => $week->id,
            'day_number' => 2,
            'main_lift' => 'bench',
        ]);

        $this->actingAs($coach)
            ->get("/program-builder?assignment={$assignment->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ProgramBuilderPage')
                ->has('dayTableLayouts')
                ->has('activeBlock')
                ->where('activeBlock.id', $assignment->id)
                ->where('activeBlock.sessions.1-2.blocks.0.lift', 'bench'));
    }

    /**
     * @return array{0: User, 1: User}
     */
    private function seedCoachAthlete(): array
    {
        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach-block@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => 'athlete-block@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);

        $coach->athletes()->attach($athlete->id, ['status' => 'active']);

        return [$coach, $athlete];
    }
}
