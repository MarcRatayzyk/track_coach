<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CoachFeedbackMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function coach(Request $request, CoachFeedbackMetricsService $feedbackMetrics): JsonResponse
    {
        $coach = $request->user();
        $feedback = $feedbackMetrics->forCoach($coach);

        return response()->json([
            'feedback' => $feedback,
            'athletes_count' => $coach->athletes()->count(),
        ]);
    }
}
