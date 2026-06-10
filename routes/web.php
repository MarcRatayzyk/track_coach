<?php

use App\Http\Controllers\Web\AccountSetupController;
use App\Http\Controllers\Web\AppPageController;
use App\Http\Controllers\Web\AthleteCompetitionController;
use App\Http\Controllers\Web\AthleteBodyWeightController;
use App\Http\Controllers\Web\AthleteReadinessController;
use App\Http\Controllers\Web\Coach\AthleteDataWebController;
use App\Http\Controllers\Web\Coach\ExerciseLibraryController;
use App\Http\Controllers\Web\Coach\CoachAthleteRosterController;
use App\Http\Controllers\Web\Coach\DashboardTaskController;
use App\Http\Controllers\Web\Coach\MessageWebController;
use App\Http\Controllers\Web\Coach\CoachChartTemplateWebController;
use App\Http\Controllers\Web\Coach\CoachStatsDashboardWebController;
use App\Http\Controllers\Web\Coach\DayTableLayoutWebController;
use App\Http\Controllers\Web\Coach\ProgramWebController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\SessionFeedbackWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::get('/account/setup/{user}', [AccountSetupController::class, 'show'])
    ->middleware('signed')
    ->name('account.setup.show');
Route::post('/account/setup/{user}', [AccountSetupController::class, 'update'])
    ->middleware('signed')
    ->name('account.setup.update');

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::get('/athlete/dashboard', [AppPageController::class, 'athleteDashboard'])
        ->name('athlete.dashboard');
    Route::get('/athlete/program', [AppPageController::class, 'athleteProgram'])
        ->name('athlete.program');

    Route::get('/feedbacks', [SessionFeedbackWebController::class, 'index'])->name('feedbacks.index');
    Route::get('/feedbacks/{feedback}', [SessionFeedbackWebController::class, 'show'])->name('feedbacks.show');
    Route::post('/feedbacks', [SessionFeedbackWebController::class, 'store'])->name('feedbacks.store');
    Route::post('/feedbacks/{feedback}/reply', [SessionFeedbackWebController::class, 'reply'])->name('feedbacks.reply');

    Route::get('/athletes/{athlete}', [AppPageController::class, 'athlete'])->name('athletes.show');
    Route::post('/athletes/{athlete}/training-sessions', [AthleteDataWebController::class, 'storeTrainingSession'])
        ->name('athletes.training-sessions.store');
    Route::put('/athletes/{athlete}/training-sessions/{trainingSession}', [AthleteDataWebController::class, 'updateTrainingSession'])
        ->name('athletes.training-sessions.update');
    Route::delete('/athletes/{athlete}/training-sessions/{trainingSession}', [AthleteDataWebController::class, 'destroyTrainingSession'])
        ->name('athletes.training-sessions.destroy');
    Route::post('/athletes/{athlete}/readiness', [AthleteReadinessController::class, 'store'])
        ->name('athletes.readiness.store');
    Route::post('/athletes/{athlete}/body-weight', [AthleteBodyWeightController::class, 'store'])
        ->name('athletes.body-weight.store');
    Route::patch('/athletes/{athlete}/competitions/{competition}/match-plan', [AthleteCompetitionController::class, 'updateMatchPlan'])
        ->name('athletes.competitions.match-plan.update');
    Route::post('/athletes/{athlete}/prs', [AthleteDataWebController::class, 'storePr'])
        ->name('athletes.prs.store');

    Route::get('/messaging', [AppPageController::class, 'messaging'])->name('messaging');
    Route::post('/coach/threads/{thread}/messages', [MessageWebController::class, 'storeMessage'])
        ->name('coach.threads.messages.store');
});

Route::middleware(['auth', 'coach'])->group(function (): void {
    Route::patch('/coach/tasks/{task}/complete', [DashboardTaskController::class, 'complete'])
        ->name('coach.tasks.complete');

    Route::get('/coach/exercises', [ExerciseLibraryController::class, 'index'])
        ->name('coach.exercises.index');
    Route::post('/coach/program-blocks', [ProgramWebController::class, 'storeBlock'])
        ->name('coach.program-blocks.store');
    Route::delete('/coach/program-blocks/{assignment}', [ProgramWebController::class, 'destroyBlock'])
        ->name('coach.program-blocks.destroy');
    Route::post('/coach/program-blocks/{assignment}/assign', [ProgramWebController::class, 'assignBlock'])
        ->name('coach.program-blocks.assign');
    Route::put('/coach/program-blocks/{assignment}/sessions', [ProgramWebController::class, 'upsertSession'])
        ->name('coach.program-blocks.sessions.upsert');
    Route::post('/coach/program-blocks/{assignment}/sessions/bulk', [ProgramWebController::class, 'bulkUpsertSessions'])
        ->name('coach.program-blocks.sessions.bulk');
    Route::delete('/coach/program-blocks/{assignment}/sessions', [ProgramWebController::class, 'clearSession'])
        ->name('coach.program-blocks.sessions.clear');

    Route::post('/coach/day-table-layouts', [DayTableLayoutWebController::class, 'store'])
        ->name('coach.day-table-layouts.store');
    Route::put('/coach/day-table-layouts/{layout}', [DayTableLayoutWebController::class, 'update'])
        ->name('coach.day-table-layouts.update');
    Route::delete('/coach/day-table-layouts/{layout}', [DayTableLayoutWebController::class, 'destroy'])
        ->name('coach.day-table-layouts.destroy');

    Route::post('/coach/chart-templates', [CoachChartTemplateWebController::class, 'store'])
        ->name('coach.chart-templates.store');
    Route::put('/coach/chart-templates/{template}', [CoachChartTemplateWebController::class, 'update'])
        ->name('coach.chart-templates.update');
    Route::delete('/coach/chart-templates/{template}', [CoachChartTemplateWebController::class, 'destroy'])
        ->name('coach.chart-templates.destroy');

    Route::post('/coach/stats-dashboard-items', [CoachStatsDashboardWebController::class, 'store'])
        ->name('coach.stats-dashboard-items.store');
    Route::delete('/coach/stats-dashboard-items/{item}', [CoachStatsDashboardWebController::class, 'destroy'])
        ->name('coach.stats-dashboard-items.destroy');
    Route::patch('/coach/stats-dashboard-items/{item}/move', [CoachStatsDashboardWebController::class, 'move'])
        ->name('coach.stats-dashboard-items.move');

    Route::post('/coach/athletes', [CoachAthleteRosterController::class, 'store'])
        ->name('coach.athletes.store');
    Route::delete('/coach/athletes/{athlete}', [CoachAthleteRosterController::class, 'destroy'])
        ->name('coach.athletes.destroy');

    Route::patch('/coach/athletes/{athlete}/profile', [AthleteDataWebController::class, 'updateProfile'])
        ->name('coach.athletes.profile.update');
    Route::post('/coach/athletes/{athlete}/prs', [AthleteDataWebController::class, 'storePr'])
        ->name('coach.athletes.prs.store');
    Route::post('/coach/athletes/{athlete}/competitions', [AthleteDataWebController::class, 'storeCompetition'])
        ->name('coach.athletes.competitions.store');
    Route::patch('/coach/athletes/{athlete}/competitions/{competition}', [AthleteDataWebController::class, 'updateCompetition'])
        ->name('coach.athletes.competitions.update');
    Route::delete('/coach/athletes/{athlete}/competitions/{competition}', [AthleteDataWebController::class, 'destroyCompetition'])
        ->name('coach.athletes.competitions.destroy');

    Route::post('/coach/threads', [MessageWebController::class, 'storeThread'])
        ->name('coach.threads.store');

    Route::get('/dashboard', [AppPageController::class, 'dashboard'])->name('dashboard');
    Route::get('/athletes', [AppPageController::class, 'athletes'])->name('athletes.index');
    Route::get('/program-builder', [AppPageController::class, 'programBuilder'])->name('program.builder');
});
