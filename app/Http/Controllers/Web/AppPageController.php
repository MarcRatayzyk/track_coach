<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AthleteProgramAssignment;
use App\Models\Competition;
use App\Models\MessageThread;
use App\Models\SessionFeedback;
use App\Services\CoachAlertsService;
use App\Services\CoachAthleteRosterService;
use App\Services\CoachFeedbackMetricsService;
use App\Models\ProgramTemplate;
use App\Models\User;
use App\Support\ActiveProgramAssignmentSupport;
use App\Support\CoachCalendarSupport;
use App\Support\AthleteDashboardPresenter;
use App\Support\AthleteBodyWeightPresenter;
use App\Support\AthleteReadinessPresenter;
use App\Support\MessagingInboxSupport;
use App\Support\MessagePresenter;
use App\Support\ChartTemplatePresenter;
use App\Support\ChartTemplateSupport;
use App\Support\CoachMonthlyReadinessAwardsPresenter;
use App\Support\DayTableLayoutPresenter;
use App\Support\DayTableLayoutSupport;
use App\Support\ProgramBlockPresenter;
use App\Support\ProgramHistorySupport;
use App\Support\ReadinessFormSupport;
use App\Support\SessionFeedbackPresenter;
use App\Support\TrainingSessionSupport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppPageController extends Controller
{
    public function dashboard(
        CoachFeedbackMetricsService $feedbackMetrics,
        CoachAlertsService $alertsService,
    ): Response {
        $coach = auth()->user();

        $athleteIds = $coach->athletes()
            ->where('users.role', 'athlete')
            ->pluck('users.id');

        $feedback = $feedbackMetrics->forCoach($coach);

        $upcomingCompetitions = Competition::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('competition_date', '>=', now()->toDateString())
            ->with('athlete:id,name')
            ->orderBy('competition_date')
            ->get();

        $nextCompetition = $upcomingCompetitions->first();
        $competitionSummary = [
            'count' => $upcomingCompetitions->count(),
            'next_date' => $nextCompetition?->competition_date?->toDateString(),
            'next_name' => $nextCompetition?->name,
            'next_athlete_name' => $nextCompetition?->athlete?->name,
        ];

        $recentThreads = MessageThread::query()
            ->where('coach_id', $coach->id)
            ->withUnreadCountFor($coach)
            ->withCount('messages')
            ->with([
                'athlete:id,name',
                'latestMessage.sender:id,name',
                'latestMessage.audioFiles',
            ])
            ->orderByDesc('updated_at')
            ->limit(24)
            ->get()
            ->sortByDesc(fn (MessageThread $thread) => [
                (int) ($thread->unread_messages_count > 0),
                $thread->updated_at?->timestamp ?? 0,
            ])
            ->take(6)
            ->values()
            ->map(fn (MessageThread $thread) => MessagingInboxSupport::threadListItem($thread, $coach))
            ->all();

        $activeProgramsCount = AthleteProgramAssignment::query()
            ->whereIn('athlete_id', $athleteIds)
            ->where('status', 'active')
            ->count();

        $templatesCount = ProgramTemplate::query()
            ->where('coach_id', $coach->id)
            ->count();

        $alerts = $alertsService->forCoach($coach);

        $calendarReminders = CoachCalendarSupport::remindersForCoach($coach);
        $calendarCompetitions = CoachCalendarSupport::competitionsForCoach($coach, $athleteIds);
        $calendarBlockEvents = CoachCalendarSupport::blockEventsForCoach($coach, $athleteIds);

        $rosterAthletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->orderBy('users.name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $athlete) => [
                'id' => $athlete->id,
                'name' => $athlete->name,
            ])
            ->values()
            ->all();

        $coachReadinessForm = ReadinessFormSupport::formPayload(
            ReadinessFormSupport::ensureCoachHasDefaultForm($coach),
        );

        $monthlyReadinessAwards = app(CoachMonthlyReadinessAwardsPresenter::class)->forCoach($coach);

        $hasRepliedToFeedback = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->where('status', 'coach_replied')
            ->exists();

        $onboardingSteps = [
            [
                'key' => 'add_athlete',
                'label' => 'Ajoute ton premier athlète',
                'description' => 'Invite un athlète pour commencer à le suivre.',
                'href' => route('athletes.index'),
                'done' => $athleteIds->count() > 0,
            ],
            [
                'key' => 'create_program',
                'label' => 'Crée un premier programme',
                'description' => 'Construis un bloc d’entraînement dans le Program Builder.',
                'href' => route('program.builder'),
                'done' => $templatesCount > 0,
            ],
            [
                'key' => 'assign_program',
                'label' => 'Assigne un programme actif',
                'description' => 'Active un bloc pour l’un de tes athlètes.',
                'href' => route('program.builder'),
                'done' => $activeProgramsCount > 0,
            ],
            [
                'key' => 'reply_feedback',
                'label' => 'Réponds à un retour de séance',
                'description' => 'Analyse une vidéo et renvoie tes conseils.',
                'href' => route('feedbacks.index'),
                'done' => $hasRepliedToFeedback,
            ],
        ];

        $onboarding = [
            'steps' => $onboardingSteps,
            'completed_count' => collect($onboardingSteps)->where('done', true)->count(),
            'total' => count($onboardingSteps),
        ];

        return Inertia::render('DashboardPage', [
            'athleteCount' => $athleteIds->count(),
            'onboarding' => $onboarding,
            'feedback' => $feedback,
            'competitionSummary' => $competitionSummary,
            'upcomingCompetitions' => $upcomingCompetitions,
            'recentThreads' => $recentThreads,
            'alerts' => $alerts,
            'calendarReminders' => $calendarReminders,
            'calendarCompetitions' => $calendarCompetitions,
            'calendarBlockEvents' => $calendarBlockEvents,
            'rosterAthletes' => $rosterAthletes,
            'coachReadinessForm' => $coachReadinessForm,
            'monthlyReadinessAwards' => $monthlyReadinessAwards,
            'stats' => [
                'active_programs' => $activeProgramsCount,
                'program_templates' => $templatesCount,
            ],
        ]);
    }

    public function athletes(CoachAthleteRosterService $rosterService): Response
    {
        $coach = auth()->user();
        $coachForm = ReadinessFormSupport::ensureCoachHasDefaultForm($coach);

        return Inertia::render('AthletesListPage', [
            'athletes' => $rosterService->rowsForCoach($coach),
            'coachReadinessForm' => ReadinessFormSupport::formPayload($coachForm),
        ]);
    }

    public function athlete(User $athlete, ProgramHistorySupport $programHistory): Response
    {
        $this->authorize('view', $athlete);
        $viewer = auth()->user();

        $athlete->load([
            'profile',
            'personalRecords',
            'latestPr',
            'trainingSessions' => fn ($q) => $q->orderByDesc('session_date')->orderByDesc('id'),
            'competitions' => fn ($q) => $q->orderBy('competition_date'),
        ]);

        $todayReadiness = null;
        $readinessRecent = [];
        $readinessForm = null;
        $todayBodyWeight = null;
        $bodyWeightRecent = [];
        $coachReadinessForm = null;

        if ($viewer?->id === $athlete->id || $viewer?->role === 'coach') {
            $readiness = AthleteReadinessPresenter::forAthlete($athlete);
            $todayReadiness = $readiness['todayReadiness'];
            $readinessRecent = $readiness['readinessRecent'];
            $readinessForm = $readiness['readinessForm'];

            $bodyWeight = AthleteBodyWeightPresenter::forAthlete($athlete);
            $todayBodyWeight = $bodyWeight['todayBodyWeight'];
            $bodyWeightRecent = $bodyWeight['bodyWeightRecent'];
        }

        if ($viewer?->role === 'coach') {
            $coachForm = ReadinessFormSupport::ensureCoachHasDefaultForm($viewer);
            $coachReadinessForm = ReadinessFormSupport::formPayload($coachForm);
        }

        $activeProgram = ActiveProgramAssignmentSupport::forAthleteDisplay($athlete);

        $followUpStartedAt = $athlete->coaches()
            ->wherePivot('status', 'active')
            ->orderBy('coach_athlete.created_at')
            ->first()
            ?->pivot
            ?->created_at
            ?->toDateString();

        $athletePayload = $athlete->toArray();
        $athletePayload['training_sessions'] = $athlete->trainingSessions
            ->map(fn ($session) => TrainingSessionSupport::toPayload($session))
            ->values()
            ->all();

        return Inertia::render('AthleteDetailPage', [
            'athlete' => $athletePayload,
            'activeProgram' => $activeProgram,
            'programBlock' => ProgramBlockPresenter::forAssignment($activeProgram),
            'followUpStartedAt' => $followUpStartedAt,
            'todayReadiness' => $todayReadiness,
            'readinessRecent' => $readinessRecent,
            'readinessForm' => $readinessForm,
            'coachReadinessForm' => $coachReadinessForm,
            'todayBodyWeight' => $todayBodyWeight,
            'bodyWeightRecent' => $bodyWeightRecent,
            'programHistory' => $viewer?->role === 'coach'
                ? $programHistory->historyForAthlete($athlete->id)
                : [],
        ]);
    }

    public function athleteDashboard(): Response|RedirectResponse
    {
        $athlete = auth()->user();

        if ($athlete->role !== 'athlete') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('AthleteDashboardPage', AthleteDashboardPresenter::forAthlete($athlete));
    }

    public function athleteProgram(): Response|RedirectResponse
    {
        $athlete = auth()->user();

        if ($athlete->role !== 'athlete') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('AthleteProgramPage', AthleteDashboardPresenter::forAthleteProgram($athlete));
    }

    public function programBuilder(Request $request): Response
    {
        $coach = auth()->user();

        $athletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->orderBy('users.name')
            ->select('users.id', 'users.name')
            ->get();

        $templateIds = ProgramTemplate::query()
            ->where('coach_id', $coach->id)
            ->pluck('id');

        $existingBlocks = AthleteProgramAssignment::query()
            ->whereIn('template_id', $templateIds)
            ->whereIn('status', ['active', 'draft'])
            ->with(['athlete:id,name', 'template.weeks'])
            ->latest('date_start')
            ->get();

        $activeBlock = null;
        $assignmentId = (int) $request->query('assignment', 0);

        if ($assignmentId > 0) {
            $assignment = AthleteProgramAssignment::query()
                ->whereKey($assignmentId)
                ->whereIn('template_id', $templateIds)
                ->first();

            $activeBlock = ProgramBlockPresenter::forAssignment($assignment);
        }

        DayTableLayoutSupport::ensureCoachHasDefaultLayout($coach);
        $dayTableLayouts = DayTableLayoutPresenter::listForCoach($coach->id);
        $defaultDayTableLayoutId = collect($dayTableLayouts)
            ->firstWhere('is_default', true)['id']
            ?? ($dayTableLayouts[0]['id'] ?? null);

        ChartTemplateSupport::ensureCoachHasDefaultDashboard($coach);
        $chartTemplates = ChartTemplatePresenter::listTemplatesForCoach($coach->id);
        $statsDashboardItems = ChartTemplatePresenter::listDashboardItemsForCoach($coach->id);

        return Inertia::render('ProgramBuilderPage', [
            'athletes' => $athletes,
            'starterPrograms' => \App\Support\StarterProgramLibrary::catalog(),
            'existingBlocks' => ProgramBlockPresenter::existingBlocksList($existingBlocks),
            'activeBlock' => $activeBlock,
            'dayTableLayouts' => $dayTableLayouts,
            'defaultDayTableLayoutId' => $defaultDayTableLayoutId,
            'chartTemplates' => $chartTemplates,
            'statsDashboardItems' => $statsDashboardItems,
        ]);
    }

    public function messaging(Request $request): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user->role === 'athlete') {
            return $this->athleteMessaging($request, $user);
        }

        return $this->coachMessaging($request, $user);
    }

    private function athleteMessaging(Request $request, User $athlete): Response|RedirectResponse
    {
        $thread = MessagingInboxSupport::threadForAthlete($athlete);

        if ($thread === null) {
            return Inertia::render('MessagingPage', [
                'role' => 'athlete',
                'threads' => [],
                'activeThread' => null,
                'messages' => [],
                'athletesForThread' => [],
                'feedbackContext' => null,
            ]);
        }

        $activeThreadId = (int) $request->query('thread', $thread->id);

        if ($activeThreadId !== $thread->id) {
            return redirect()->route('messaging', ['thread' => $thread->id]);
        }

        $thread->loadMissing(['coach:id,name', 'athlete:id,name']);
        $thread->markAsReadFor($athlete);

        $messages = $thread->messages()
            ->with(['sender:id,name', 'audioFiles', 'sessionFeedback.programTrainingDay'])
            ->orderBy('created_at')
            ->get();

        return Inertia::render('MessagingPage', [
            'role' => 'athlete',
            'threads' => [MessagingInboxSupport::threadListItem($thread, $athlete)],
            'activeThread' => [
                'id' => $thread->id,
                'coach' => $thread->coach ? [
                    'id' => $thread->coach->id,
                    'name' => $thread->coach->name,
                ] : null,
                'athlete' => $thread->athlete ? [
                    'id' => $thread->athlete->id,
                    'name' => $thread->athlete->name,
                ] : null,
            ],
            'messages' => MessagePresenter::list($messages),
            'athletesForThread' => [],
            'feedbackContext' => null,
        ]);
    }

    private function coachMessaging(Request $request, User $user): Response|RedirectResponse
    {
        $activeThreadId = (int) $request->query('thread', 0);
        $athleteId = (int) $request->query('athlete', 0);
        $feedbackId = (int) $request->query('feedback', 0);
        $feedbackContext = null;

        if ($feedbackId > 0) {
            $feedback = SessionFeedback::query()
                ->where('coach_id', $user->id)
                ->with('programTrainingDay')
                ->find($feedbackId);

            if ($feedback !== null && $user->can('view', $feedback)) {
                $thread = MessageThread::firstOrCreate([
                    'coach_id' => $user->id,
                    'athlete_id' => $feedback->athlete_id,
                ]);

                if ($activeThreadId === 0) {
                    $activeThreadId = $thread->id;
                }

                $feedbackContext = [
                    'id' => $feedback->id,
                    'session_date' => $feedback->session_date?->toDateString(),
                    'session_label' => SessionFeedbackPresenter::sessionLabel($feedback->programTrainingDay),
                    'can_reply' => $feedback->isPendingCoachReply(),
                ];
            }
        }

        if ($activeThreadId === 0 && $athleteId > 0) {
            $athlete = User::query()->whereKey($athleteId)->first();

            if ($athlete !== null && $request->user()->can('updateAthleteData', $athlete)) {
                $thread = MessageThread::firstOrCreate([
                    'coach_id' => $user->id,
                    'athlete_id' => $athlete->id,
                ]);

                return redirect()->route('messaging', [
                    'thread' => $thread->id,
                    'feedback' => $feedbackId > 0 ? $feedbackId : null,
                ]);
            }
        }

        $activeThread = null;
        $messages = collect();

        if ($activeThreadId > 0) {
            $candidate = MessageThread::query()
                ->where('coach_id', $user->id)
                ->whereKey($activeThreadId)
                ->with('athlete')
                ->first();

            if ($candidate !== null && $request->user()->can('view', $candidate)) {
                $activeThread = $candidate;
                $candidate->markAsReadFor($user);
                $messages = $candidate->messages()
                    ->with(['sender:id,name', 'audioFiles', 'sessionFeedback.programTrainingDay'])
                    ->orderBy('created_at')
                    ->get();
            }
        }

        $threads = MessageThread::query()
            ->where('coach_id', $user->id)
            ->orderedForInbox($user)
            ->withCount('messages')
            ->with([
                'athlete:id,name',
                'latestMessage.sender:id,name',
                'latestMessage.audioFiles',
            ])
            ->get()
            ->map(fn (MessageThread $thread) => MessagingInboxSupport::threadListItem($thread, $user))
            ->values()
            ->all();

        return Inertia::render('MessagingPage', [
            'role' => 'coach',
            'threads' => $threads,
            'activeThread' => $activeThread ? [
                'id' => $activeThread->id,
                'athlete' => $activeThread->athlete ? [
                    'id' => $activeThread->athlete->id,
                    'name' => $activeThread->athlete->name,
                ] : null,
            ] : null,
            'messages' => MessagePresenter::list($messages),
            'athletesForThread' => [],
            'feedbackContext' => $feedbackContext,
        ]);
    }
}
