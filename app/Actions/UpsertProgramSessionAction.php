<?php

namespace App\Actions;

use App\Http\Requests\StoreProgramSessionRequest;
use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Support\ProgramSessionSerializer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpsertProgramSessionAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(StoreProgramSessionRequest $request, AthleteProgramAssignment $assignment): array
    {
        return DB::transaction(function () use ($request, $assignment): array {
            $week = ProgramWeek::query()
                ->where('template_id', $assignment->template_id)
                ->where('week_number', $request->integer('week_number'))
                ->first();

            if ($week === null) {
                throw ValidationException::withMessages([
                    'week_number' => 'Semaine invalide pour ce bloc.',
                ]);
            }

            $items = $request->input('items', []);
            $blocks = $request->input('blocks', []);
            $primaryLift = $request->input('main_lift')
                ?? ($items[0]['lift'] ?? null)
                ?? ($blocks[0]['lift'] ?? 'squat');

            $sessionLabel = $request->input('session_label');
            $sessionLabel = is_string($sessionLabel) && trim($sessionLabel) !== ''
                ? trim($sessionLabel)
                : null;

            $notes = $request->input('notes');
            $notes = is_string($notes) && trim($notes) !== ''
                ? trim($notes)
                : null;

            $day = ProgramTrainingDay::query()->updateOrCreate(
                [
                    'week_id' => $week->id,
                    'day_number' => $request->integer('weekday'),
                ],
                [
                    'main_lift' => $primaryLift,
                    'session_label' => $sessionLabel,
                    'notes' => $notes,
                ],
            );

            ProgramSessionSerializer::persistExercises($day, [
                'main_lift' => $primaryLift,
                'items' => $items,
                'blocks' => $blocks,
            ]);

            $day->load('week');

            return ProgramSessionSerializer::trainingDayToPayload($day);
        });
    }
}
