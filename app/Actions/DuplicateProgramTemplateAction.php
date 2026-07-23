<?php

namespace App\Actions;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DuplicateProgramTemplateAction
{
    /**
     * Deep-clone a program template (weeks -> days -> exercises) into a new
     * template owned by the coach, and create a fresh draft assignment for the
     * given athlete.
     */
    public function execute(
        ProgramTemplate $source,
        User $coach,
        int $athleteId,
        ?CarbonInterface $dateStart = null,
        ?string $name = null,
    ): AthleteProgramAssignment {
        return DB::transaction(function () use ($source, $coach, $athleteId, $dateStart, $name): AthleteProgramAssignment {
            $source->loadMissing('weeks.trainingDays.exercises');

            $newTemplate = ProgramTemplate::create([
                'coach_id' => $coach->id,
                'name' => $name ?: $source->name.' (copie)',
                'goal' => $source->goal,
                'level' => $source->level,
                'table_layout' => $source->table_layout,
                'default_warmup_notes' => $source->default_warmup_notes,
                'default_warmup_items' => $source->default_warmup_items,
            ]);

            $weekCount = 0;

            foreach ($source->weeks as $week) {
                $weekCount++;

                $newWeek = $newTemplate->weeks()->create([
                    'week_number' => $week->week_number,
                    'block_type' => $week->block_type,
                ]);

                foreach ($week->trainingDays as $day) {
                    $newDay = $newWeek->trainingDays()->create([
                        'day_number' => $day->day_number,
                        'main_lift' => $day->main_lift,
                        'session_label' => $day->session_label,
                        'notes' => $day->notes,
                        'warmup_override' => $day->warmup_override,
                        'warmup_notes' => $day->warmup_notes,
                    ]);

                    foreach ($day->exercises as $exercise) {
                        $newDay->exercises()->create([
                            'block_index' => $exercise->block_index,
                            'lift' => $exercise->lift,
                            'exercise_variant_id' => $exercise->exercise_variant_id,
                            'section' => $exercise->section,
                            'exercise_name' => $exercise->exercise_name,
                            'sets' => $exercise->sets,
                            'reps' => $exercise->reps,
                            'load' => $exercise->load,
                            'load_percent' => $exercise->load_percent,
                            'rpe' => $exercise->rpe,
                            'rest_seconds' => $exercise->rest_seconds,
                            'sort_order' => $exercise->sort_order,
                        ]);
                    }
                }
            }

            $start = $dateStart ? Carbon::parse($dateStart) : now()->startOfDay();
            $end = $weekCount > 0 ? $start->copy()->addWeeks($weekCount)->subDay() : null;

            return AthleteProgramAssignment::create([
                'athlete_id' => $athleteId,
                'template_id' => $newTemplate->id,
                'date_start' => $start,
                'date_end' => $end,
                'status' => 'draft',
            ]);
        });
    }
}
