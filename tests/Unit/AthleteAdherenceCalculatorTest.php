<?php

namespace Tests\Unit;

use App\Models\AthleteProgramAssignment;
use App\Models\ProgramDayExercise;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Models\TrainingSession;
use App\Models\User;
use App\Support\AthleteAdherenceCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AthleteAdherenceCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_adherence_requires_matching_sets_reps_and_load(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-16 12:00:00'));

        $athlete = User::query()->create([
            'name' => 'Athlete',
            'email' => uniqid('adherence-', true).'@test.dev',
            'password' => bcrypt('password'),
            'role' => 'athlete',
        ]);

        $coach = User::query()->create([
            'name' => 'Coach',
            'email' => uniqid('coach-adherence-', true).'@test.dev',
            'password' => bcrypt('password'),
            'role' => 'coach',
        ]);

        $template = ProgramTemplate::query()->create([
            'coach_id' => $coach->id,
            'name' => 'Bloc',
        ]);

        $week = ProgramWeek::query()->create([
            'template_id' => $template->id,
            'week_number' => 1,
            'block_type' => ProgramWeek::BLOCK_VOLUME,
        ]);

        $day = ProgramTrainingDay::query()->create([
            'week_id' => $week->id,
            'day_number' => 1,
            'main_lift' => ProgramTrainingDay::LIFT_SQUAT,
        ]);

        ProgramDayExercise::query()->create([
            'training_day_id' => $day->id,
            'block_index' => 0,
            'section' => ProgramDayExercise::SECTION_TOPSET,
            'exercise_name' => 'Squat',
            'sets' => 3,
            'reps' => 5,
            'load' => 100,
            'sort_order' => 0,
        ]);

        $assignment = AthleteProgramAssignment::query()->create([
            'athlete_id' => $athlete->id,
            'template_id' => $template->id,
            'date_start' => '2026-05-12',
            'status' => 'active',
        ]);

        $session = TrainingSession::query()->create([
            'athlete_id' => $athlete->id,
            'session_date' => '2026-05-12',
            'main_lift' => 'squat',
            'items' => [
                [
                    'exercise_name' => 'Squat',
                    'sets' => 3,
                    'reps' => 5,
                    'load' => 100,
                    'lift' => 'squat',
                ],
            ],
        ]);

        $perfect = app(AthleteAdherenceCalculator::class)->between(
            $athlete->id,
            $assignment,
            Carbon::parse('2026-05-12'),
            Carbon::parse('2026-05-12'),
        );

        $this->assertSame(100, $perfect['percentage']);

        $session->update([
            'items' => [
                [
                    'exercise_name' => 'Squat',
                    'sets' => 3,
                    'reps' => 5,
                    'load' => 80,
                    'lift' => 'squat',
                ],
            ],
        ]);

        $assignment->refresh();

        $mismatch = app(AthleteAdherenceCalculator::class)->between(
            $athlete->id,
            $assignment,
            Carbon::parse('2026-05-12'),
            Carbon::parse('2026-05-12'),
        );

        $this->assertLessThan(100, $mismatch['percentage']);

        Carbon::setTestNow();
    }
}
