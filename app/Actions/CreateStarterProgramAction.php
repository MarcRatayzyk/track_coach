<?php

namespace App\Actions;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTemplate;
use App\Models\User;
use App\Support\DayTableLayoutSupport;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreateStarterProgramAction
{
    /**
     * Materialize a starter program definition into a template + draft
     * assignment owned by the coach.
     *
     * @param  array<string, mixed>  $definition
     */
    public function execute(
        array $definition,
        User $coach,
        int $athleteId,
        ?CarbonInterface $dateStart = null,
    ): AthleteProgramAssignment {
        if (empty($definition['weeks'])) {
            throw new RuntimeException('Starter program definition has no weeks.');
        }

        return DB::transaction(function () use ($definition, $coach, $athleteId, $dateStart): AthleteProgramAssignment {
            $layout = DayTableLayoutSupport::ensureCoachHasDefaultLayout($coach);

            $template = ProgramTemplate::create([
                'coach_id' => $coach->id,
                'name' => $definition['name'],
                'goal' => $definition['goal'] ?? null,
                'level' => $definition['level'] ?? 'intermediate',
                'table_layout' => $layout->toSnapshot(),
            ]);

            $weekCount = 0;

            foreach ($definition['weeks'] as $week) {
                $weekCount++;

                $programWeek = $template->weeks()->create([
                    'week_number' => $week['week_number'],
                    'block_type' => $week['block_type'] ?? 'volume',
                ]);

                foreach ($week['days'] ?? [] as $day) {
                    $trainingDay = $programWeek->trainingDays()->create([
                        'day_number' => $day['day_number'],
                        'main_lift' => $day['main_lift'],
                        'session_label' => $day['session_label'] ?? ('Jour '.$day['day_number']),
                    ]);

                    $sortOrder = 0;

                    foreach ($day['exercises'] ?? [] as $exercise) {
                        $sortOrder++;

                        $trainingDay->exercises()->create([
                            'block_index' => 0,
                            'lift' => $exercise['lift'] ?? $day['main_lift'],
                            'section' => $exercise['section'] ?? 'topset',
                            'exercise_name' => $exercise['exercise_name'],
                            'sets' => $exercise['sets'] ?? 1,
                            'reps' => $exercise['reps'] ?? 1,
                            'load_percent' => $exercise['load_percent'] ?? null,
                            'rpe' => $exercise['rpe'] ?? null,
                            'sort_order' => $sortOrder,
                        ]);
                    }
                }
            }

            $start = $dateStart ? Carbon::parse($dateStart) : now()->startOfDay();
            $end = $start->copy()->addWeeks($weekCount)->subDay();

            return AthleteProgramAssignment::create([
                'athlete_id' => $athleteId,
                'template_id' => $template->id,
                'date_start' => $start,
                'date_end' => $end,
                'status' => 'draft',
            ]);
        });
    }
}
