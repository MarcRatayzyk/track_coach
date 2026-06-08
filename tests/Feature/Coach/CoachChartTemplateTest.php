<?php

namespace Tests\Feature\Coach;

use App\Models\CoachChartTemplate;
use App\Models\CoachStatsDashboardItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoachChartTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_program_builder_page_includes_chart_templates_and_dashboard_items(): void
    {
        [$coach] = $this->seedCoachWithDefaultDashboard();

        $this->actingAs($coach)
            ->get('/program-builder?tab=stats')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ProgramBuilderPage')
                ->has('chartTemplates', 0)
                ->has('statsDashboardItems', 4)
                ->where('statsDashboardItems.0.builtin_key', 'volume_weekly'));
    }

    public function test_coach_can_create_chart_template(): void
    {
        [$coach] = $this->seedCoachWithDefaultDashboard();

        $this->actingAs($coach)
            ->post('/coach/chart-templates', [
                'name' => 'Volume topsets',
                'chartType' => 'bar',
                'metric' => 'volume',
                'groupBy' => 'week',
                'series' => ['squat', 'bench'],
                'stacked' => true,
                'filters' => [
                    'mainLift' => 'all',
                    'repFormat' => 'all',
                    'section' => 'topset',
                ],
            ])
            ->assertRedirect(route('program.builder', ['tab' => 'stats']));

        $this->assertDatabaseHas('coach_chart_templates', [
            'coach_id' => $coach->id,
            'name' => 'Volume topsets',
        ]);
    }

    public function test_coach_can_create_template_and_add_to_dashboard(): void
    {
        [$coach] = $this->seedCoachWithDefaultDashboard();

        $this->actingAs($coach)
            ->post('/coach/chart-templates', [
                'name' => 'Charge moyenne',
                'chartType' => 'line',
                'metric' => 'avgLoad',
                'groupBy' => 'week',
                'series' => ['squat'],
                'add_to_dashboard' => true,
            ])
            ->assertRedirect();

        $template = CoachChartTemplate::query()->where('name', 'Charge moyenne')->first();
        $this->assertNotNull($template);
        $this->assertDatabaseHas('coach_stats_dashboard_items', [
            'coach_id' => $coach->id,
            'item_type' => CoachStatsDashboardItem::TYPE_CUSTOM,
            'template_id' => $template->id,
        ]);
    }

    public function test_coach_can_update_chart_template(): void
    {
        [$coach, $template] = $this->seedCoachWithTemplate();

        $this->actingAs($coach)
            ->put("/coach/chart-templates/{$template->id}", [
                'name' => 'Volume modifié',
                'chartType' => 'doughnut',
                'metric' => 'volume',
                'groupBy' => 'lift',
                'series' => ['squat', 'bench', 'deadlift'],
            ])
            ->assertRedirect(route('program.builder', ['tab' => 'stats']));

        $this->assertDatabaseHas('coach_chart_templates', [
            'id' => $template->id,
            'name' => 'Volume modifié',
        ]);
    }

    public function test_coach_cannot_update_another_coach_template(): void
    {
        [$coach, $template] = $this->seedCoachWithTemplate();
        $otherCoach = User::query()->create([
            'name' => 'Other Coach',
            'email' => 'other-chart-coach@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $this->actingAs($otherCoach)
            ->put("/coach/chart-templates/{$template->id}", [
                'name' => 'Hack',
                'chartType' => 'bar',
                'metric' => 'volume',
                'groupBy' => 'week',
                'series' => ['squat'],
            ])
            ->assertForbidden();
    }

    public function test_coach_can_remove_dashboard_item(): void
    {
        [$coach] = $this->seedCoachWithDefaultDashboard();

        $item = CoachStatsDashboardItem::query()
            ->where('coach_id', $coach->id)
            ->where('builtin_key', 'volume_weekly')
            ->first();

        $this->actingAs($coach)
            ->delete("/coach/stats-dashboard-items/{$item->id}")
            ->assertRedirect(route('program.builder', ['tab' => 'stats']));

        $this->assertDatabaseMissing('coach_stats_dashboard_items', ['id' => $item->id]);
    }

    public function test_coach_can_add_existing_template_to_dashboard(): void
    {
        [$coach, $template] = $this->seedCoachWithTemplate();

        $this->actingAs($coach)
            ->post('/coach/stats-dashboard-items', [
                'template_id' => $template->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('coach_stats_dashboard_items', [
            'coach_id' => $coach->id,
            'template_id' => $template->id,
            'item_type' => CoachStatsDashboardItem::TYPE_CUSTOM,
        ]);
    }

    public function test_coach_can_delete_chart_template(): void
    {
        [$coach, $template] = $this->seedCoachWithTemplate();

        $this->actingAs($coach)
            ->delete("/coach/chart-templates/{$template->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('coach_chart_templates', ['id' => $template->id]);
    }

    /**
     * @return array{0: User}
     */
    private function seedCoachWithDefaultDashboard(): array
    {
        $coach = User::query()->create([
            'name' => 'Coach Chart',
            'email' => 'coach-chart@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        foreach (CoachStatsDashboardItem::builtinKeys() as $index => $builtinKey) {
            CoachStatsDashboardItem::create([
                'coach_id' => $coach->id,
                'item_type' => CoachStatsDashboardItem::TYPE_BUILTIN,
                'builtin_key' => $builtinKey,
                'sort_order' => $index,
            ]);
        }

        return [$coach];
    }

    /**
     * @return array{0: User, 1: CoachChartTemplate}
     */
    private function seedCoachWithTemplate(): array
    {
        [$coach] = $this->seedCoachWithDefaultDashboard();

        $template = CoachChartTemplate::create([
            'coach_id' => $coach->id,
            'name' => 'Mon graphique',
            'config' => [
                'chartType' => 'bar',
                'metric' => 'volume',
                'groupBy' => 'week',
                'series' => ['squat'],
                'stacked' => false,
                'filters' => [
                    'mainLift' => 'all',
                    'repFormat' => 'all',
                    'section' => 'all',
                    'weekFrom' => null,
                    'weekTo' => null,
                    'exerciseName' => null,
                ],
            ],
        ]);

        return [$coach, $template];
    }
}
