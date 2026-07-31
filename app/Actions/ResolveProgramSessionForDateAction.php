<?php

namespace App\Actions;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTrainingDay;
use App\Models\User;
use App\Support\ActiveProgramAssignmentSupport;
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
        $assignment = ActiveProgramAssignmentSupport::forAthleteOnDate(
            $athlete,
            $sessionDate,
            ['template.weeks.trainingDays'],
        );

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
