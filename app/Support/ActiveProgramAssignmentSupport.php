<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\User;
use Carbon\CarbonInterface;

class ActiveProgramAssignmentSupport
{
    public static function forAthleteOnDate(
        User $athlete,
        ?CarbonInterface $date = null,
        array $with = ['template.weeks.trainingDays.exercises'],
    ): ?AthleteProgramAssignment {
        $date = ($date ?? now())->copy()->startOfDay();

        // date_start peut être en milieu de semaine : le calendrier (cellDate) couvre
        // dès le lundi ISO, donc on élargit le filtre SQL puis on valide via ProgramSchedule.
        $candidates = $athlete->programAssignments()
            ->where('status', 'active')
            ->whereDate('date_start', '<=', $date->copy()->addDays(6)->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $date->toDateString());
            })
            ->with($with)
            ->latest('date_start')
            ->get();

        return $candidates->first(
            fn (AthleteProgramAssignment $assignment): bool => ProgramSchedule::isDateOnSchedule($assignment, $date),
        );
    }

    /**
     * Bloc actif aujourd'hui, ou prochain bloc assigné pas encore commencé.
     */
    public static function forAthleteDisplay(
        User $athlete,
        ?CarbonInterface $date = null,
        array $with = ['template.weeks.trainingDays.exercises'],
    ): ?AthleteProgramAssignment {
        $current = self::forAthleteOnDate($athlete, $date, $with);

        if ($current !== null) {
            return $current;
        }

        $date = ($date ?? now())->copy()->startOfDay();

        return $athlete->programAssignments()
            ->where('status', 'active')
            ->whereDate('date_start', '>', $date->toDateString())
            ->where(function ($query) use ($date): void {
                $query->whereNull('date_end')
                    ->orWhereDate('date_end', '>=', $date->toDateString());
            })
            ->with($with)
            ->orderBy('date_start')
            ->first();
    }
}
