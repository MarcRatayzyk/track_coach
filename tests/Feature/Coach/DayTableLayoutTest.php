<?php

namespace Tests\Feature\Coach;

use App\Models\AthleteProgramAssignment;
use App\Models\DayTableLayout;
use App\Models\ProgramTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayTableLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_builder_page_includes_day_table_layouts(): void
    {
        [$coach] = $this->seedCoachWithDefaultLayout();

        $this->actingAs($coach)
            ->get('/program-builder?tab=table')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ProgramBuilderPage')
                ->has('dayTableLayouts', 1)
                ->where('dayTableLayouts.0.name', 'Classique')
                ->has('defaultDayTableLayoutId'));
    }

    public function test_coach_can_create_day_table_layout(): void
    {
        [$coach] = $this->seedCoachWithDefaultLayout();

        $this->actingAs($coach)
            ->post('/coach/day-table-layouts', [
                'name' => 'Split PL',
                'columns' => ['section', 'sets', 'reps', 'load', 'rest'],
                'exercise_mode' => 'split_lift',
                'load_mode' => 'rpe',
                'is_default' => false,
            ])
            ->assertRedirect(route('program.builder', ['tab' => 'table']));

        $this->assertDatabaseHas('day_table_layouts', [
            'coach_id' => $coach->id,
            'name' => 'Split PL',
            'exercise_mode' => 'split_lift',
            'load_mode' => 'rpe',
            'is_default' => false,
        ]);
    }

    public function test_coach_can_update_day_table_layout(): void
    {
        [$coach, $layout] = $this->seedCoachWithDefaultLayout();

        $this->actingAs($coach)
            ->put("/coach/day-table-layouts/{$layout->id}", [
                'name' => 'Tableur RPE',
                'columns' => ['section', 'sets', 'reps', 'load'],
                'exercise_mode' => 'name',
                'load_mode' => 'rpe',
                'is_default' => true,
            ])
            ->assertRedirect(route('program.builder', ['tab' => 'table']));

        $this->assertDatabaseHas('day_table_layouts', [
            'id' => $layout->id,
            'name' => 'Tableur RPE',
            'load_mode' => 'rpe',
            'is_default' => true,
        ]);
    }

    public function test_coach_cannot_update_another_coach_layout(): void
    {
        [$coach, $layout] = $this->seedCoachWithDefaultLayout();
        $otherCoach = User::query()->create([
            'name' => 'Other Coach',
            'email' => 'other-coach@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $this->actingAs($otherCoach)
            ->put("/coach/day-table-layouts/{$layout->id}", [
                'name' => 'Hack',
                'columns' => ['sets', 'reps', 'load'],
                'exercise_mode' => 'name',
                'load_mode' => 'kg',
            ])
            ->assertForbidden();
    }

    public function test_coach_cannot_delete_last_day_table_layout(): void
    {
        [$coach, $layout] = $this->seedCoachWithDefaultLayout();

        $this->actingAs($coach)
            ->delete("/coach/day-table-layouts/{$layout->id}")
            ->assertRedirect(route('program.builder', ['tab' => 'table']));

        $this->assertDatabaseHas('day_table_layouts', ['id' => $layout->id]);
    }

    public function test_block_creation_snapshots_selected_day_table_layout(): void
    {
        [$coach, , $athlete] = $this->seedCoachAthleteWithLayout();

        $layout = DayTableLayout::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Split',
            'columns' => ['sets', 'reps', 'load', 'rest'],
            'exercise_mode' => 'split_lift',
            'load_mode' => 'percent',
            'is_default' => false,
        ]);

        $this->actingAs($coach)
            ->post('/coach/program-blocks', [
                'athlete_id' => $athlete->id,
                'name' => 'Bloc tableur',
                'week_count' => 2,
                'days_per_week' => 3,
                'date_start' => '2026-06-02',
                'day_table_layout_id' => $layout->id,
                'builder_tab' => 'table',
            ])
            ->assertRedirect();

        $template = ProgramTemplate::query()->where('name', 'Bloc tableur')->first();
        $this->assertNotNull($template);
        $this->assertSame([
            'columns' => ['sets', 'reps', 'load', 'rest'],
            'exercise_mode' => 'split_lift',
            'load_mode' => 'percent',
        ], $template->table_layout);
    }

    public function test_table_session_items_can_store_rest_seconds(): void
    {
        [$coach, $athlete, $assignment] = $this->seedCoachAthleteAssignment();

        $this->actingAs($coach)
            ->put("/coach/program-blocks/{$assignment->id}/sessions", [
                'week_number' => 1,
                'weekday' => 1,
                'main_lift' => 'squat',
                'session_label' => 'Jour 1',
                'items' => [
                    [
                        'section' => 'topset',
                        'exercise_name' => 'Squat classique',
                        'sets' => 1,
                        'reps' => 3,
                        'load' => 160,
                        'rest_seconds' => 180,
                    ],
                ],
                'blocks' => [],
                'builder_tab' => 'table',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('program_day_exercises', [
            'exercise_name' => 'Squat classique',
            'rest_seconds' => 180,
        ]);
    }

    /**
     * @return array{0: User, 1: DayTableLayout}
     */
    private function seedCoachWithDefaultLayout(): array
    {
        $coach = User::query()->create([
            'name' => 'Coach Layout',
            'email' => 'coach-layout@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);
        $layout = DayTableLayout::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Classique',
            'columns' => ['section', 'sets', 'reps', 'load'],
            'exercise_mode' => DayTableLayout::EXERCISE_MODE_NAME,
            'load_mode' => DayTableLayout::LOAD_MODE_KG,
            'is_default' => true,
        ]);

        return [$coach, $layout];
    }

    /**
     * @return array{0: User, 1: DayTableLayout, 2: User}
     */
    private function seedCoachAthleteWithLayout(): array
    {
        [$coach, $layout] = $this->seedCoachWithDefaultLayout();
        $athlete = User::query()->create([
            'name' => 'Athlete Layout',
            'email' => 'athlete-layout@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);
        $coach->athletes()->attach($athlete->id, ['status' => 'active']);

        return [$coach, $layout, $athlete];
    }

    /**
     * @return array{0: User, 1: User, 2: \App\Models\AthleteProgramAssignment}
     */
    private function seedCoachAthleteAssignment(): array
    {
        [$coach, , $athlete] = $this->seedCoachAthleteWithLayout();

        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Bloc test',
            'level' => 'intermediate',
            'table_layout' => DayTableLayout::classicSnapshot(),
        ]);

        $week = $template->weeks()->create([
            'week_number' => 1,
            'block_type' => 'volume',
        ]);

        $week->trainingDays()->create([
            'day_number' => 1,
            'main_lift' => 'squat',
            'session_label' => 'Jour 1',
        ]);

        $assignment = AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-06-02',
            'status' => 'draft',
        ]);

        return [$coach, $athlete, $assignment];
    }
}
