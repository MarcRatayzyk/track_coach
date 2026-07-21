<?php

namespace App\Actions;

use App\Http\Requests\UpdateProgramBlockWarmupRequest;
use App\Models\AthleteProgramAssignment;
use Illuminate\Support\Facades\DB;

class UpdateProgramBlockWarmupAction
{
    public function execute(
        UpdateProgramBlockWarmupRequest $request,
        AthleteProgramAssignment $assignment,
    ): AthleteProgramAssignment {
        return DB::transaction(function () use ($request, $assignment): AthleteProgramAssignment {
            $assignment->loadMissing('template');

            $notes = $request->input('default_warmup_notes');
            $notes = is_string($notes) && trim($notes) !== '' ? trim($notes) : null;

            $items = [];
            foreach ($request->input('default_warmup_items', []) as $item) {
                if (! is_array($item) || empty(trim((string) ($item['exercise_name'] ?? '')))) {
                    continue;
                }

                $items[] = [
                    'exercise_variant_id' => $item['exercise_variant_id'] ?? null,
                    'exercise_name' => trim((string) $item['exercise_name']),
                    'lift' => $item['lift'] ?? null,
                    'sets' => isset($item['sets']) ? (int) $item['sets'] : null,
                    'reps' => isset($item['reps']) ? (int) $item['reps'] : null,
                    'load' => $item['load'] ?? null,
                    'load_percent' => $item['load_percent'] ?? null,
                    'rpe' => $item['rpe'] ?? null,
                    'rest_seconds' => $item['rest_seconds'] ?? null,
                ];
            }

            $assignment->template->update([
                'default_warmup_notes' => $notes,
                'default_warmup_items' => $items === [] ? null : $items,
            ]);

            return $assignment->fresh(['template.weeks.trainingDays.exercises']);
        });
    }
}
