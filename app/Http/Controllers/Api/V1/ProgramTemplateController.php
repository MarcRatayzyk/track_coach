<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\StoreProgramTemplateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignProgramTemplateRequest;
use App\Http\Requests\StoreProgramTemplateRequest;
use App\Models\AthleteProgramAssignment;
use App\Models\ProgramTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgramTemplateController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $templates = ProgramTemplate::query()
            ->where('coach_id', $request->user()->id)
            ->with('weeks.trainingDays.exercises')
            ->latest()
            ->paginate(20);

        return response()->json($templates);
    }

    public function store(StoreProgramTemplateRequest $request, StoreProgramTemplateAction $action): JsonResponse
    {
        $template = $action->execute($request);

        return response()->json($template, 201);
    }

    public function assign(AssignProgramTemplateRequest $request, ProgramTemplate $template): JsonResponse
    {
        $this->authorize('assign', $template);

        $assignment = AthleteProgramAssignment::create([
            'athlete_id' => $request->integer('athlete_id'),
            'template_id' => $template->id,
            'date_start' => $request->date('date_start'),
            'date_end' => $request->filled('date_end') ? $request->date('date_end') : null,
            'status' => 'active',
        ]);

        return response()->json($assignment, 201);
    }
}
