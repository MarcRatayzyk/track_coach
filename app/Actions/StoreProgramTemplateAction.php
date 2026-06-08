<?php

namespace App\Actions;

use App\Http\Requests\StoreProgramTemplateRequest;
use App\Models\ProgramDayExercise;
use App\Models\ProgramTemplate;
use Illuminate\Support\Facades\DB;

class StoreProgramTemplateAction
{
    public function execute(StoreProgramTemplateRequest $request): ProgramTemplate
    {
        return DB::transaction(function () use ($request): ProgramTemplate {
            $template = ProgramTemplate::create([
                'coach_id' => $request->user()->id,
                'name' => $request->string('name')->toString(),
                'goal' => $request->string('goal')->toString(),
                'level' => $request->string('level')->toString(),
            ]);

            foreach ($request->input('weeks', []) as $weekData) {
                $week = $template->weeks()->create([
                    'week_number' => $weekData['week_number'],
                    'block_type' => $weekData['block_type'],
                ]);

                foreach ($weekData['days'] ?? [] as $dayData) {
                    $day = $week->trainingDays()->create([
                        'day_number' => $dayData['day_number'],
                        'main_lift' => $dayData['main_lift'],
                    ]);

                    $sortOrder = 0;

                    if (! empty($dayData['topset'])) {
                        $this->createExerciseLine($day->id, ProgramDayExercise::SECTION_TOPSET, $dayData['topset'], $sortOrder++);
                    }

                    if (! empty($dayData['backoff'])) {
                        $this->createExerciseLine($day->id, ProgramDayExercise::SECTION_BACKOFF, $dayData['backoff'], $sortOrder++);
                    }

                    foreach ($dayData['accessories'] ?? [] as $accessory) {
                        $this->createExerciseLine($day->id, ProgramDayExercise::SECTION_ACCESSORY, $accessory, $sortOrder++);
                    }
                }
            }

            return $template->load([
                'weeks.trainingDays.exercises',
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $line
     */
    private function createExerciseLine(int $trainingDayId, string $section, array $line, int $sortOrder): void
    {
        ProgramDayExercise::create([
            'training_day_id' => $trainingDayId,
            'exercise_variant_id' => $line['exercise_variant_id'] ?? null,
            'section' => $section,
            'exercise_name' => $line['exercise_name'],
            'sets' => $line['sets'],
            'reps' => $line['reps'],
            'load' => $line['load'] ?? null,
            'rpe' => $line['rpe'] ?? null,
            'sort_order' => $sortOrder,
        ]);
    }
}
