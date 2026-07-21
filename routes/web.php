<?php

use App\Http\Controllers\Web\CompetitionLiveController;
use App\Http\Controllers\Web\EmailVerificationController;
use App\Http\Controllers\Web\ForgotPasswordController;
use App\Http\Controllers\Web\ResetPasswordController;
use App\Http\Controllers\Web\AccountSetupController;
use App\Http\Controllers\Web\AppPageController;
use App\Http\Controllers\Web\AthleteCompetitionController;
use App\Http\Controllers\Web\AthleteBodyWeightController;
use App\Http\Controllers\Web\AthleteReadinessController;
use App\Http\Controllers\Web\Coach\AthleteDataWebController;
use App\Http\Controllers\Web\Coach\AthleteProgramHistoryController;
use App\Http\Controllers\Web\Coach\ProgramPdfExportController;
use App\Http\Controllers\Web\Coach\SessionFeedbackAnnotationController;
use App\Http\Controllers\Web\Coach\ExerciseLibraryController;
use App\Http\Controllers\Web\Coach\CoachCalendarReminderController;
use App\Http\Controllers\Web\Coach\CoachAthleteRosterController;
use App\Http\Controllers\Web\Coach\CoachProfileController;
use App\Http\Controllers\Web\Coach\CoachReadinessFormController;
use App\Http\Controllers\Web\Coach\DashboardTaskController;
use App\Http\Controllers\Web\Coach\MessageWebController;
use App\Http\Controllers\Web\Coach\CoachChartTemplateWebController;
use App\Http\Controllers\Web\Coach\CoachStatsDashboardWebController;
use App\Http\Controllers\Web\Coach\DayTableLayoutWebController;
use App\Http\Controllers\Web\Coach\ProgramWebController;
use App\Http\Controllers\Web\RegisterController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\LoginController;
use App\Http\Controllers\Web\SessionFeedbackWebController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingController::class)->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->name('password.update');
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
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:verification'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:verification')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/athlete/dashboard', [AppPageController::class, 'athleteDashboard'])
        ->name('athlete.dashboard');
    Route::get('/athlete/program', [AppPageController::class, 'athleteProgram'])
        ->name('athlete.program');
    Route::get('/coaches/{coach}', [CoachProfileController::class, 'show'])
        ->name('coaches.show');

    Route::get('/feedbacks', [SessionFeedbackWebController::class, 'index'])->name('feedbacks.index');
    Route::get('/feedbacks/{feedback}', [SessionFeedbackWebController::class, 'show'])->name('feedbacks.show');
    Route::post('/feedbacks', [SessionFeedbackWebController::class, 'store'])->name('feedbacks.store');

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
    Route::get('/athletes/{athlete}/competitions/{competition}/live', [CompetitionLiveController::class, 'show'])
        ->name('athletes.competitions.live.show');
    Route::patch('/athletes/{athlete}/competitions/{competition}/live', [CompetitionLiveController::class, 'update'])
        ->name('athletes.competitions.live.update');
    Route::post('/athletes/{athlete}/prs', [AthleteDataWebController::class, 'storePr'])
        ->name('athletes.prs.store');
    Route::patch('/athletes/{athlete}/profile', [AthleteDataWebController::class, 'updateOwnProfile'])
        ->name('athletes.profile.update');
    Route::post('/athletes/{athlete}/competitions', [AthleteDataWebController::class, 'storeCompetition'])
        ->name('athletes.competitions.store');
    Route::patch('/athletes/{athlete}/competitions/{competition}', [AthleteDataWebController::class, 'updateCompetition'])
        ->name('athletes.competitions.update');
    Route::delete('/athletes/{athlete}/competitions/{competition}', [AthleteDataWebController::class, 'destroyCompetition'])
        ->name('athletes.competitions.destroy');

    Route::get('/messaging', [AppPageController::class, 'messaging'])->name('messaging');
    Route::post('/coach/threads/{thread}/messages', [MessageWebController::class, 'storeMessage'])
        ->middleware('throttle:messages')
        ->name('coach.threads.messages.store');
    Route::post('/messaging/threads/{thread}/messages', [MessageWebController::class, 'storeMessage'])
        ->middleware('throttle:messages')
        ->name('messaging.threads.messages.store');
});

Route::middleware(['auth', 'verified', 'coach'])->group(function (): void {
    Route::patch('/coach/tasks/{task}/complete', [DashboardTaskController::class, 'complete'])
        ->name('coach.tasks.complete');

    Route::get('/coach/exercises', [ExerciseLibraryController::class, 'index'])
        ->name('coach.exercises.index');
    Route::post('/coach/exercises', [ExerciseLibraryController::class, 'store'])
        ->name('coach.exercises.store');
    Route::put('/coach/exercises/{exercise}', [ExerciseLibraryController::class, 'update'])
        ->name('coach.exercises.update');
    Route::delete('/coach/exercises/{exercise}', [ExerciseLibraryController::class, 'destroy'])
        ->name('coach.exercises.destroy');

    Route::post('/coach/session-feedback-media/{media}/annotations', [SessionFeedbackAnnotationController::class, 'store'])
        ->name('coach.session-feedback-annotations.store');
    Route::put('/coach/session-feedback-annotations/{annotation}', [SessionFeedbackAnnotationController::class, 'update'])
        ->name('coach.session-feedback-annotations.update');
    Route::delete('/coach/session-feedback-annotations/{annotation}', [SessionFeedbackAnnotationController::class, 'destroy'])
        ->name('coach.session-feedback-annotations.destroy');
    Route::post('/coach/program-blocks', [ProgramWebController::class, 'storeBlock'])
        ->name('coach.program-blocks.store');
    Route::get('/coach/program-blocks/{assignment}/export-pdf', ProgramPdfExportController::class)
        ->name('coach.program-blocks.export-pdf');
    Route::delete('/coach/program-blocks/{assignment}', [ProgramWebController::class, 'destroyBlock'])
        ->name('coach.program-blocks.destroy');
    Route::post('/coach/program-blocks/{assignment}/assign', [ProgramWebController::class, 'assignBlock'])
        ->name('coach.program-blocks.assign');
    Route::put('/coach/program-blocks/{assignment}/warmup', [ProgramWebController::class, 'updateWarmup'])
        ->name('coach.program-blocks.warmup.update');
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
    Route::post('/coach/athletes/{athlete}/resend-invitation', [CoachAthleteRosterController::class, 'resendInvitation'])
        ->name('coach.athletes.resend-invitation');
    Route::delete('/coach/athletes/{athlete}', [CoachAthleteRosterController::class, 'destroy'])
        ->name('coach.athletes.destroy');

    Route::put('/coach/readiness-form', [CoachReadinessFormController::class, 'updateTemplate'])
        ->name('coach.readiness-form.update');
    Route::put('/coach/athletes/{athlete}/readiness-form', [CoachReadinessFormController::class, 'updateAthleteForm'])
        ->name('coach.athletes.readiness-form.update');

    Route::patch('/coach/athletes/{athlete}/profile', [AthleteDataWebController::class, 'updateProfile'])
        ->name('coach.athletes.profile.update');
    Route::get('/athletes/{athlete}/program-history', [AthleteProgramHistoryController::class, 'index'])
        ->name('athletes.program-history.index');
    Route::get('/athletes/{athlete}/program-history/compare', [AthleteProgramHistoryController::class, 'compare'])
        ->name('athletes.program-history.compare');
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
    Route::get('/coach/profile', [CoachProfileController::class, 'ownProfile'])->name('coach.profile');
    Route::patch('/coach/profile', [CoachProfileController::class, 'update'])->name('coach.profile.update');
    Route::post('/coach/calendar-reminders', [CoachCalendarReminderController::class, 'store'])
        ->name('coach.calendar-reminders.store');
    Route::patch('/coach/calendar-reminders/{reminder}', [CoachCalendarReminderController::class, 'update'])
        ->name('coach.calendar-reminders.update');
    Route::delete('/coach/calendar-reminders/{reminder}', [CoachCalendarReminderController::class, 'destroy'])
        ->name('coach.calendar-reminders.destroy');
    Route::get('/athletes', [AppPageController::class, 'athletes'])->name('athletes.index');
    Route::get('/program-builder', [AppPageController::class, 'programBuilder'])->name('program.builder');
});
