<?php

namespace App\Http\Controllers\Web\Coach;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ProgramHistorySupport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AthleteProgramHistoryController extends Controller
{
    public function index(Request $request, User $athlete, ProgramHistorySupport $history): JsonResponse
    {
        $this->authorize('view', $athlete);

        return response()->json([
            'blocks' => $history->historyForAthlete($athlete->id),
        ]);
    }

    public function compare(Request $request, User $athlete, ProgramHistorySupport $history): JsonResponse
    {
        $this->authorize('view', $athlete);

        $validated = $request->validate([
            'ids' => ['required', 'array', 'size:2'],
            'ids.*' => ['integer', 'exists:athlete_program_assignments,id'],
        ]);

        return response()->json([
            'blocks' => $history->compare($athlete->id, $validated['ids']),
        ]);
    }
}
