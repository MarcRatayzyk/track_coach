<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCompetitionRequest;
use App\Http\Requests\StorePersonalRecordRequest;
use App\Models\Competition;
use App\Models\PersonalRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AthleteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $coach = $request->user();

        $athletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->with(['profile', 'latestPr', 'upcomingCompetition'])
            ->paginate(20);

        return response()->json($athletes);
    }

    public function show(Request $request, User $athlete): JsonResponse
    {
        $this->authorize('view', $athlete);

        $athlete->load(['profile', 'personalRecords', 'competitions']);

        return response()->json($athlete);
    }

    public function storePr(StorePersonalRecordRequest $request, User $athlete): JsonResponse
    {
        $user = $request->user();

        if ($user->role === 'coach') {
            $this->authorize('updateAthleteData', $athlete);
        } else {
            $this->authorize('recordOwnPr', $athlete);
        }

        $record = PersonalRecord::create([
            'athlete_id' => $athlete->id,
            ...$request->validated(),
        ]);

        return response()->json($record, 201);
    }

    public function storeCompetition(StoreCompetitionRequest $request, User $athlete): JsonResponse
    {
        $this->authorize('updateAthleteData', $athlete);

        $competition = Competition::create([
            'athlete_id' => $athlete->id,
            ...$request->competitionPayload(),
        ]);

        return response()->json($competition, 201);
    }

    public function activeProgram(Request $request, User $athlete): JsonResponse
    {
        $this->authorize('view', $athlete);

        $assignment = $athlete->programAssignments()
            ->with('template.weeks.trainingDays.exercises')
            ->where('status', 'active')
            ->latest('date_start')
            ->first();

        return response()->json([
            'athlete_id' => $athlete->id,
            'program' => $assignment,
        ]);
    }
}
