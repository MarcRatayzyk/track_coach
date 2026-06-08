<?php

use App\Http\Controllers\Api\V1\AthleteController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProgramTemplateController;
use App\Http\Controllers\Api\V1\ThreadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/login', [AuthController::class, 'login']);
        Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    });

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/athletes', [AthleteController::class, 'index']);
        Route::get('/athletes/{athlete}', [AthleteController::class, 'show']);
        Route::post('/athletes/{athlete}/prs', [AthleteController::class, 'storePr']);
        Route::post('/athletes/{athlete}/competitions', [AthleteController::class, 'storeCompetition']);
        Route::get('/athletes/{athlete}/program', [AthleteController::class, 'activeProgram']);

        Route::post('/program-templates', [ProgramTemplateController::class, 'store']);
        Route::get('/program-templates', [ProgramTemplateController::class, 'index']);
        Route::post('/program-templates/{template}/assign', [ProgramTemplateController::class, 'assign']);

        Route::get('/threads', [ThreadController::class, 'index']);
        Route::post('/threads', [ThreadController::class, 'store']);
        Route::get('/threads/{thread}/messages', [ThreadController::class, 'messages']);
        Route::post('/threads/{thread}/messages', [ThreadController::class, 'storeMessage']);

        Route::get('/dashboard/coach', [DashboardController::class, 'coach']);
    });
});
