<?php

namespace App\Actions;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\User;
use App\Support\ProgramSchedule;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class ResolveProgramSessionForDateAction
{
    /**
     * @return array{
     *     assignment: AthleteProgramAssignment,
     *     training_day: ProgramTrainingDay,
     *     coach: User,
     * }
     */
    public function execute(User $athlete, CarbonInterface $sessionDate): array
    {
        $assignment = $athlete->programAssignments()
            ->where('status', 'active')
            ->whereDate('date_start', '<=', $sessionDate->toDateString())
            ->where(function ($query) use ($sessionDate): void {
                $query->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $sessionDate->toDateString());
            })
            ->with('template.weeks.trainingDays')
            ->latest('date_start')
            ->first();

        if ($assignment === null) {
            throw ValidationException::withMessages([
                'session_date' => 'Aucun programme actif pour cette date.',
            ]);
        }

        $trainingDay = ProgramSchedule::resolveTrainingDayForDate($assignment, $sessionDate);
        if ($trainingDay === null) {
            throw ValidationException::withMessages([
                'session_date' => 'Aucune séance programme prévue pour cette date.',
            ]);
        }

        $coach = $athlete->coaches()
            ->where('users.role', 'coach')
            ->wherePivot('status', 'active')
            ->first();

        if ($coach === null) {
            throw ValidationException::withMessages([
                'session_date' => 'Aucun coach associé à votre compte.',
            ]);
        }

        return [
            'assignment' => $assignment,
            'training_day' => $trainingDay,
            'coach' => $coach,
        ];
    }
}
