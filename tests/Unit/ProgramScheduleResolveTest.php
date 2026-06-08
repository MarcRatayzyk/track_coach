<?php

namespace Tests\Unit;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\User;
use App\Support\ProgramSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProgramScheduleResolveTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_training_day_for_date_in_first_week(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-20 12:00:00')); // Wednesday

        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => 'coach-schedule@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);
        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => 'athlete-schedule@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);
        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Bloc',
            'level' => 'intermediate',
        ]);

        $week = ProgramWeek::query()->create([
            'template_id' => $template->id,
            'week_number' => 1,
            'block_type' => ProgramWeek::BLOCK_VOLUME,
        ]);

        $wednesday = ProgramTrainingDay::query()->create([
            'week_id' => $week->id,
            'day_number' => 3,
            'main_lift' => ProgramTrainingDay::LIFT_SQUAT,
            'session_label' => 'Séance force',
        ]);

        ProgramTrainingDay::query()->create([
            'week_id' => $week->id,
            'day_number' => 1,
            'main_lift' => ProgramTrainingDay::LIFT_BENCH,
        ]);

        $assignment = AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-05-18',
            'status' => 'active',
        ]);

        $assignment->load('template.weeks.trainingDays');

        $resolved = ProgramSchedule::resolveTrainingDayForDate(
            $assignment,
            Carbon::parse('2026-05-20'),
        );

        $this->assertNotNull($resolved);
        $this->assertSame($wednesday->id, $resolved->id);

        $noSession = ProgramSchedule::resolveTrainingDayForDate(
            $assignment,
            Carbon::parse('2026-05-19'), // Tuesday, no day_number 2
        );

        $this->assertNull($noSession);

        Carbon::setTestNow();
    }
}
