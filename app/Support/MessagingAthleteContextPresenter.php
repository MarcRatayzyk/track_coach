<?php

namespace App\Support;

use App\Models\SessionFeedback;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\AthleteSessionCoverageService;
use Carbon\Carbon;

class MessagingAthleteContextPresenter
{
    /**
     * @return array<string, mixed>|null
     */
    public static function forAthlete(?User $athlete): ?array
    {
        if ($athlete === null) {
            return null;
        }

        $athlete->loadMissing([
            'profile',
            'latestPr',
            'upcomingCompetition',
            'personalRecords' => fn ($query) => $query->orderByDesc('reference_date')->limit(3),
        ]);

        $profile = $athlete->profile;
        $pr = $athlete->latestPr;
        $bodyWeight = AthleteBodyWeightPresenter::forAthlete($athlete, 30);
        $latestWeight = $bodyWeight['todayBodyWeight']
            ?? ($bodyWeight['bodyWeightRecent'][0] ?? null);

        $lastSession = TrainingSession::query()
            ->where('athlete_id', $athlete->id)
            ->orderByDesc('session_date')
            ->orderByDesc('id')
            ->first();

        $activeProgram = ActiveProgramAssignmentSupport::forAthleteDisplay(
            $athlete,
            null,
            ['template:id,name,goal'],
        );

        $weekStart = now()->copy()->startOfWeek(Carbon::MONDAY);
        $weekEnd = now()->copy()->endOfWeek(Carbon::SUNDAY);
        $sessionsThisWeek = TrainingSession::query()
            ->where('athlete_id', $athlete->id)
            ->whereDate('session_date', '>=', $weekStart->toDateString())
            ->whereDate('session_date', '<=', $weekEnd->toDateString())
            ->count();

        $adherence = null;
        if ($activeProgram !== null) {
            $coverage = app(AthleteSessionCoverageService::class)->coverageBetween(
                $athlete->id,
                $activeProgram,
                $weekStart,
                now()->copy()->startOfDay(),
            );
            $adherence = $coverage['percentage'] ?? null;
        }

        $recentVideos = SessionFeedback::query()
            ->where('athlete_id', $athlete->id)
            ->with(['athleteVideos', 'programTrainingDay'])
            ->latest('session_date')
            ->limit(3)
            ->get()
            ->map(fn (SessionFeedback $feedback) => [
                'id' => $feedback->id,
                'session_date' => $feedback->session_date?->toDateString(),
                'session_label' => SessionFeedbackPresenter::sessionLabel($feedback->programTrainingDay),
                'videos_count' => $feedback->athleteVideos->count(),
            ])
            ->values()
            ->all();

        $nextCompetition = $athlete->upcomingCompetition;
        $goal = $activeProgram?->template?->goal
            ?? $nextCompetition?->goal
            ?? null;

        $recentPrs = $athlete->personalRecords
            ->take(3)
            ->map(fn ($record) => [
                'id' => $record->id,
                'reference_date' => $record->reference_date?->toDateString(),
                'squat' => (int) ($record->squat ?? 0),
                'bench' => (int) ($record->bench ?? 0),
                'deadlift' => (int) ($record->deadlift ?? 0),
                'total' => (int) ($record->squat ?? 0) + (int) ($record->bench ?? 0) + (int) ($record->deadlift ?? 0),
            ])
            ->values()
            ->all();

        return [
            'id' => $athlete->id,
            'name' => $athlete->name,
            'club' => null,
            'category' => $profile?->weight_category,
            'category_label' => IpfWeightCategorySupport::labelForCategory($profile?->weight_category),
            'level' => $profile?->level,
            'level_label' => IpfWeightCategorySupport::labelForLevel($profile?->level),
            'weight_kg' => $latestWeight['weight_kg'] ?? null,
            'weight_date' => $latestWeight['entry_date'] ?? null,
            'goal' => $goal,
            'competition' => $nextCompetition ? [
                'id' => $nextCompetition->id,
                'name' => $nextCompetition->name,
                'date' => $nextCompetition->competition_date?->toDateString(),
                'goal' => $nextCompetition->goal,
                'location' => $nextCompetition->location,
            ] : null,
            'last_session' => $lastSession ? [
                'id' => $lastSession->id,
                'date' => $lastSession->session_date?->toDateString(),
                'label' => $lastSession->session_label,
                'main_lift' => $lastSession->main_lift,
            ] : null,
            'week_volume' => [
                'sessions_count' => $sessionsThisWeek,
                'adherence_percentage' => $adherence,
            ],
            'recent_prs' => $recentPrs,
            'latest_pr' => $pr ? [
                'squat' => (int) ($pr->squat ?? 0),
                'bench' => (int) ($pr->bench ?? 0),
                'deadlift' => (int) ($pr->deadlift ?? 0),
                'total' => (int) ($pr->squat ?? 0) + (int) ($pr->bench ?? 0) + (int) ($pr->deadlift ?? 0),
                'reference_date' => $pr->reference_date?->toDateString(),
            ] : null,
            'program' => $activeProgram ? [
                'id' => $activeProgram->id,
                'name' => $activeProgram->template?->name,
                'goal' => $activeProgram->template?->goal,
                'date_start' => $activeProgram->date_start?->toDateString(),
                'date_end' => $activeProgram->date_end?->toDateString(),
            ] : null,
            'coach_notes' => $profile?->injuries_notes,
            'bio' => $profile?->bio,
            'recent_videos' => $recentVideos,
            'profile_url' => '/athletes/'.$athlete->id,
        ];
    }
}
