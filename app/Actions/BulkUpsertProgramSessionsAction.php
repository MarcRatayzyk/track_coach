<?php

namespace App\Actions;

use App\Http\Requests\BulkUpsertProgramSessionsRequest;
use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Support\ProgramSessionSerializer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BulkUpsertProgramSessionsAction
{
    public function execute(BulkUpsertProgramSessionsRequest $request, AthleteProgramAssignment $assignment): int
    {
        return DB::transaction(function () use ($request, $assignment): int {
            $weeksByNumber = ProgramWeek::query()
                ->where('template_id', $assignment->template_id)
                ->whereIn('week_number', collect($request->input('operations'))->pluck('week_number')->unique())
                ->get()
                ->keyBy('week_number');

            $count = 0;

            foreach ($request->input('operations') as $operation) {
                $weekNumber = (int) $operation['week_number'];
                $week = $weeksByNumber->get($weekNumber);

                if ($week === null) {
                    throw ValidationException::withMessages([
                        'operations' => "Semaine {$weekNumber} invalide pour ce bloc.",
                    ]);
                }

                $items = $operation['items'] ?? [];
                $blocks = $operation['blocks'] ?? [];
                $warmupOverride = (bool) ($operation['warmup_override'] ?? false);

                if (! $warmupOverride && is_array($items)) {
                    $items = array_values(array_filter(
                        $items,
                        static fn ($item): bool => ! is_array($item) || ($item['section'] ?? null) !== 'warmup',
                    ));
                }

                $hasContent = count($items) > 0
                    || count($blocks) > 0
                    || ! empty($operation['session_label'])
                    || ($warmupOverride && (! empty($operation['warmup_notes']) || collect($items)->contains(
                        fn ($item) => is_array($item) && ($item['section'] ?? null) === 'warmup',
                    )));

                if (! $hasContent) {
                    continue;
                }

                $workItems = array_values(array_filter(
                    $items,
                    static fn ($item): bool => is_array($item) && ($item['section'] ?? null) !== 'warmup',
                ));

                $primaryLift = $operation['main_lift']
                    ?? ($workItems[0]['lift'] ?? null)
                    ?? ($blocks[0]['lift'] ?? 'squat');

                $sessionLabel = $operation['session_label'] ?? null;
                $sessionLabel = is_string($sessionLabel) && trim($sessionLabel) !== ''
                    ? trim($sessionLabel)
                    : null;

                $notes = $operation['notes'] ?? null;
                $notes = is_string($notes) && trim($notes) !== ''
                    ? trim($notes)
                    : null;

                $warmupNotes = $operation['warmup_notes'] ?? null;
                $warmupNotes = $warmupOverride && is_string($warmupNotes) && trim($warmupNotes) !== ''
                    ? trim($warmupNotes)
                    : null;

                $day = ProgramTrainingDay::query()->updateOrCreate(
                    [
                        'week_id' => $week->id,
                        'day_number' => (int) $operation['weekday'],
                    ],
                    [
                        'main_lift' => $primaryLift,
                        'session_label' => $sessionLabel,
                        'notes' => $notes,
                        'warmup_override' => $warmupOverride,
                        'warmup_notes' => $warmupNotes,
                    ],
                );

                ProgramSessionSerializer::persistExercises($day, [
                    'main_lift' => $primaryLift,
                    'items' => $items,
                    'blocks' => $blocks,
                    'warmup_override' => $warmupOverride,
                ]);

                $count++;
            }

            return $count;
        });
    }
}
