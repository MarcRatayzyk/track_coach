<?php

namespace App\Actions;

use App\Http\Requests\StoreProgramBlockRequest;
use App\Models\AthleteProgramAssignment;
use App\Models\DayTableLayout;
use App\Models\ProgramTemplate;
use App\Models\ProgramTrainingDay;
use App\Models\ProgramWeek;
use App\Support\DayTableLayoutSupport;
use Illuminate\Support\Facades\DB;

class CreateProgramBlockAction
{
    public function execute(StoreProgramBlockRequest $request): AthleteProgramAssignment
    {
        return DB::transaction(function () use ($request): AthleteProgramAssignment {
            $dateStart = $request->date('date_start');
            $weekCount = $request->integer('week_count');
            $daysPerWeek = $request->integer('days_per_week');
            $dateEnd = $dateStart->copy()->addWeeks($weekCount)->subDay();
            $tableLayout = $this->resolveTableLayout($request);

            $template = ProgramTemplate::create([
                'coach_id' => $request->user()->id,
                'name' => $request->string('name')->toString(),
                'goal' => null,
                'level' => 'intermediate',
                'table_layout' => $tableLayout,
            ]);

            for ($week = 1; $week <= $weekCount; $week++) {
                $programWeek = $template->weeks()->create([
                    'week_number' => $week,
                    'block_type' => ProgramWeek::BLOCK_VOLUME,
                ]);

                for ($day = 1; $day <= $daysPerWeek; $day++) {
                    $programWeek->trainingDays()->create([
                        'day_number' => $day,
                        'main_lift' => ProgramTrainingDay::LIFT_SQUAT,
                        'session_label' => "Jour {$day}",
                    ]);
                }
            }

            return AthleteProgramAssignment::create([
                'athlete_id' => $request->integer('athlete_id'),
                'template_id' => $template->id,
                'date_start' => $dateStart,
                'date_end' => $dateEnd,
                'status' => 'draft',
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveTableLayout(StoreProgramBlockRequest $request): array
    {
        $layoutId = $request->integer('day_table_layout_id');

        if ($layoutId > 0) {
            $layout = DayTableLayout::query()
                ->whereKey($layoutId)
                ->where('coach_id', $request->user()->id)
                ->first();

            if ($layout !== null) {
                return $layout->toSnapshot();
            }
        }

        $defaultLayout = DayTableLayoutSupport::ensureCoachHasDefaultLayout($request->user());

        return $defaultLayout->toSnapshot();
    }
}
