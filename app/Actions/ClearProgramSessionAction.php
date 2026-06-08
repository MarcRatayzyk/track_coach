<?php

namespace App\Actions;

use App\Http\Requests\ClearProgramSessionRequest;
use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClearProgramSessionAction
{
    public function execute(ClearProgramSessionRequest $request, AthleteProgramAssignment $assignment): void
    {
        DB::transaction(function () use ($request, $assignment): void {
            $week = ProgramWeek::query()
                ->where('template_id', $assignment->template_id)
                ->where('week_number', $request->integer('week_number'))
                ->first();

            if ($week === null) {
                throw ValidationException::withMessages([
                    'week_number' => 'Semaine invalide pour ce bloc.',
                ]);
            }

            ProgramTrainingDay::query()
                ->where('week_id', $week->id)
                ->where('day_number', $request->integer('weekday'))
                ->delete();
        });
    }
}
