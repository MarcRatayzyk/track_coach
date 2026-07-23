<?php

namespace App\Services;

use App\Models\AthleteProgramAssignment;
use App\Models\Competition;
use App\Models\MessageThread;
use App\Models\TrainingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CoachAlertsService
{
    private const BLOCK_WARNING_DAYS = 14;

    private const BLOCK_CRITICAL_DAYS = 7;

    private const COMPETITION_WARNING_DAYS = 28;

    private const COMPETITION_CRITICAL_DAYS = 7;

    private const ADHERENCE_DROP_POINTS = 15;

    private const ADHERENCE_LOW_PERCENT = 55;

    private const ADHERENCE_HIGH_PERCENT = 85;

    private const INACTIVE_DAYS = 10;

    private const MAX_ALERTS_PER_TYPE = 2;

    public function __construct(
        private readonly AthleteSessionCoverageService $sessionCoverage,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function forCoach(User $coach): array
    {
        $today = now()->copy()->startOfDay();

        $athletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->with([
                'programAssignments' => fn ($query) => $query
                    ->where('status', 'active')
                    ->with([
                        'template.weeks.trainingDays.exercises',
                        'athlete.latestPr',
                    ]),
            ])
            ->orderBy('users.name')
            ->get(['users.id', 'users.name']);

        $athleteIds = $athletes->pluck('id');

        $recentEnd = $today->copy();
        $recentStart = $today->copy()->subDays(6);
        $previousEnd = $recentStart->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays(6);

        $sessionsByAthlete = TrainingSession::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('session_date', '>=', $previousStart->toDateString())
            ->whereDate('session_date', '<=', $recentEnd->toDateString())
            ->orderBy('session_date')
            ->orderBy('id')
            ->get()
            ->groupBy('athlete_id');

        $latestSessionDates = $this->sessionCoverage->latestSessionDates($athleteIds);

        $alerts = collect();

        foreach ($athletes as $athlete) {
            $activeAssignment = $athlete->programAssignments->first();

            if ($activeAssignment === null) {
                $alerts->push($this->makeAlert(
                    key: "no-program-{$athlete->id}",
                    type: 'no_program',
                    severity: 'info',
                    title: 'Sans programme actif',
                    body: "{$athlete->name} n'a pas de bloc en cours.",
                    href: '/program-builder',
                    athleteId: $athlete->id,
                    athleteName: $athlete->name,
                    sortDate: $today->toDateString(),
                ));

                continue;
            }

            $athleteSessions = $sessionsByAthlete->get($athlete->id, collect());

            $alerts = $alerts->merge(
                $this->blockEndingAlerts($athlete, $activeAssignment, $today),
            );
            $alerts = $alerts->merge(
                $this->adherenceAlerts(
                    $athlete,
                    $activeAssignment,
                    $today,
                    $recentStart,
                    $recentEnd,
                    $previousStart,
                    $previousEnd,
                    $athleteSessions,
                ),
            );
            $alerts = $alerts->merge(
                $this->adherenceCelebrationAlerts(
                    $athlete,
                    $activeAssignment,
                    $today,
                    $recentStart,
                    $recentEnd,
                    $athleteSessions,
                ),
            );
            $alerts = $alerts->merge(
                $this->prCelebrationAlerts($athlete, $today),
            );
            $alerts = $alerts->merge(
                $this->inactivityAlerts(
                    $athlete,
                    $activeAssignment,
                    $today,
                    $latestSessionDates->get($athlete->id),
                ),
            );
        }

        $alerts = $alerts->merge($this->competitionAlerts($athleteIds, $today));
        $alerts = $alerts->merge($this->unreadMessageAlerts($coach));

        return $this->limitAlertsPerType($this->sortAlerts($alerts))->values()->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function blockEndingAlerts(
        User $athlete,
        AthleteProgramAssignment $assignment,
        Carbon $today,
    ): Collection {
        if ($assignment->date_end === null) {
            return collect();
        }

        $daysUntilEnd = $today->diffInDays($assignment->date_end->copy()->startOfDay(), false);

        if ($daysUntilEnd < 0 || $daysUntilEnd > self::BLOCK_WARNING_DAYS) {
            return collect();
        }

        $blockName = $assignment->template?->name ?? 'Bloc';
        $severity = $daysUntilEnd <= self::BLOCK_CRITICAL_DAYS ? 'critical' : 'warning';
        $when = $daysUntilEnd === 0
            ? "aujourd'hui"
            : ($daysUntilEnd === 1 ? 'demain' : "dans {$daysUntilEnd} jours");

        return collect([
            $this->makeAlert(
                key: "block-ending-{$assignment->id}",
                type: 'block_ending',
                severity: $severity,
                title: 'Fin de bloc proche',
                body: "Le bloc « {$blockName} » de {$athlete->name} se termine {$when}.",
                href: "/program-builder?assignment={$assignment->id}",
                athleteId: $athlete->id,
                athleteName: $athlete->name,
                sortDate: $assignment->date_end->toDateString(),
            ),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function competitionAlerts(Collection $athleteIds, Carbon $today): Collection
    {
        if ($athleteIds->isEmpty()) {
            return collect();
        }

        return Competition::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('competition_date', '>=', $today->toDateString())
            ->whereDate('competition_date', '<=', $today->copy()->addDays(self::COMPETITION_WARNING_DAYS)->toDateString())
            ->with('athlete:id,name')
            ->orderBy('competition_date')
            ->get()
            ->filter(fn (Competition $competition): bool => ! $competition->hasMatchPlan())
            ->map(function (Competition $competition) use ($today): array {
                $compDate = $competition->competition_date->copy()->startOfDay();
                $daysUntil = $today->diffInDays($compDate, false);
                $severity = $daysUntil <= self::COMPETITION_CRITICAL_DAYS ? 'critical' : 'warning';
                $when = $daysUntil === 0
                    ? "aujourd'hui"
                    : ($daysUntil === 1 ? 'demain' : "dans {$daysUntil} jours");
                $name = $competition->name ?: 'Compétition';
                $athleteName = $competition->athlete?->name ?? 'Athlète';

                $body = "{$athleteName} · {$name} {$when} — le plan de match n'est pas encore défini.";

                return $this->makeAlert(
                    key: "competition-{$competition->id}",
                    type: 'competition_soon',
                    severity: $severity,
                    title: 'Plan de match à définir',
                    body: $body,
                    href: "/athletes/{$competition->athlete_id}",
                    athleteId: $competition->athlete_id,
                    athleteName: $athleteName,
                    sortDate: $competition->competition_date->toDateString(),
                );
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function adherenceAlerts(
        User $athlete,
        AthleteProgramAssignment $assignment,
        Carbon $today,
        Carbon $recentStart,
        Carbon $recentEnd,
        Carbon $previousStart,
        Carbon $previousEnd,
        Collection $athleteSessions,
    ): Collection {
        $recent = $this->sessionCoverage->coverageBetween(
            $athlete->id,
            $assignment,
            $recentStart,
            $recentEnd,
            $athleteSessions,
        );
        $previous = $this->sessionCoverage->coverageBetween(
            $athlete->id,
            $assignment,
            $previousStart,
            $previousEnd,
            $athleteSessions,
        );

        $alerts = collect();

        if (
            $recent['planned'] >= 2
            && $previous['percentage'] !== null
            && $recent['percentage'] !== null
            && ($previous['percentage'] - $recent['percentage']) >= self::ADHERENCE_DROP_POINTS
        ) {
            $drop = $previous['percentage'] - $recent['percentage'];
            $gapSummary = $this->describeAdherenceGaps($recent);
            $alerts->push($this->makeAlert(
                key: "adherence-drop-{$athlete->id}",
                type: 'adherence_drop',
                severity: 'warning',
                title: "Baisse d'adhérence",
                body: "{$athlete->name} {$gapSummary} "
                    ."Son adhérence a baissé à {$recent['percentage']} % sur 7 jours "
                    ."(contre {$previous['percentage']} % la semaine précédente, −{$drop} pts).",
                href: "/athletes/{$athlete->id}",
                athleteId: $athlete->id,
                athleteName: $athlete->name,
                sortDate: $today->toDateString(),
            ));
        } elseif (
            $recent['planned'] >= 2
            && $recent['percentage'] !== null
            && $recent['percentage'] < self::ADHERENCE_LOW_PERCENT
        ) {
            $gapSummary = $this->describeAdherenceGaps($recent);
            $alerts->push($this->makeAlert(
                key: "adherence-low-{$athlete->id}",
                type: 'adherence_low',
                severity: 'warning',
                title: 'Adhérence faible',
                body: "{$athlete->name} {$gapSummary} "
                    ."Adhérence à {$recent['percentage']} % sur les 7 derniers jours.",
                href: "/athletes/{$athlete->id}",
                athleteId: $athlete->id,
                athleteName: $athlete->name,
                sortDate: $today->toDateString(),
            ));
        }

        return $alerts;
    }

    /**
     * @param  array{
     *     planned: int,
     *     completed: int,
     *     missed?: int,
     *     missed_exercises?: int,
     *     mismatched_sets?: int,
     * }  $coverage
     */
    private function describeAdherenceGaps(array $coverage): string
    {
        $missedSessions = (int) ($coverage['missed'] ?? max(0, $coverage['planned'] - $coverage['completed']));
        $missedExercises = (int) ($coverage['missed_exercises'] ?? 0);
        $mismatchedSets = (int) ($coverage['mismatched_sets'] ?? 0);
        $parts = [];

        if ($missedSessions > 0) {
            $parts[] = sprintf(
                "n'a pas enregistré %d séance%s sur %d",
                $missedSessions,
                $missedSessions > 1 ? 's' : '',
                $coverage['planned'],
            );
        } else {
            $parts[] = sprintf(
                'a enregistré %d séance%s sur %d',
                $coverage['completed'],
                $coverage['completed'] > 1 ? 's' : '',
                $coverage['planned'],
            );
        }

        if ($missedExercises > 0) {
            $parts[] = sprintf(
                '%d exercice%s non réalisé%s',
                $missedExercises,
                $missedExercises > 1 ? 's' : '',
                $missedExercises > 1 ? 's' : '',
            );
        }

        if ($mismatchedSets > 0) {
            $parts[] = sprintf(
                '%d ligne%s avec séries non respectées',
                $mismatchedSets,
                $mismatchedSets > 1 ? 's' : '',
            );
        }

        if (count($parts) === 0) {
            return 'a un suivi incomplet sur la période.';
        }

        return implode(' ; ', $parts).'.';
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function adherenceCelebrationAlerts(
        User $athlete,
        AthleteProgramAssignment $assignment,
        Carbon $today,
        Carbon $recentStart,
        Carbon $recentEnd,
        Collection $athleteSessions,
    ): Collection {
        $recent = $this->sessionCoverage->coverageBetween(
            $athlete->id,
            $assignment,
            $recentStart,
            $recentEnd,
            $athleteSessions,
        );

        if (
            $recent['planned'] < 2
            || $recent['percentage'] === null
            || $recent['percentage'] < self::ADHERENCE_HIGH_PERCENT
        ) {
            return collect();
        }

        return collect([
            $this->makeAlert(
                key: "adherence-high-{$athlete->id}-{$today->toDateString()}",
                type: 'adherence_high',
                severity: 'info',
                title: 'Adhérence élevée',
                body: "{$athlete->name} maintient {$recent['percentage']} % d'adhérence sur 7 jours "
                    ."({$recent['completed']}/{$recent['planned']} séances"
                    .($recent['missed_exercises'] > 0
                        ? ", {$recent['missed_exercises']} exercice".($recent['missed_exercises'] > 1 ? 's' : '').' manquant'.($recent['missed_exercises'] > 1 ? 's' : '')
                        : '')
                    .').',
                href: "/athletes/{$athlete->id}",
                athleteId: $athlete->id,
                athleteName: $athlete->name,
                sortDate: $today->toDateString(),
                sharePayload: $this->buildSharePayload(
                    variant: 'adherence_high',
                    athleteName: $athlete->name,
                    date: $today->toDateString(),
                    headline: "Adhérence {$recent['percentage']} %",
                    subline: "7 derniers jours · {$recent['completed']}/{$recent['planned']} séances",
                    metrics: [
                        'adherence_percent' => $recent['percentage'],
                        'completed_sessions' => $recent['completed'],
                        'planned_sessions' => $recent['planned'],
                        'period_days' => 7,
                    ],
                ),
            ),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function prCelebrationAlerts(User $athlete, Carbon $today): Collection
    {
        $athlete->loadMissing('latestPr');
        $latestPr = $athlete->latestPr;

        if ($latestPr === null || $latestPr->reference_date === null) {
            return collect();
        }

        if ($latestPr->reference_date->toDateString() !== $today->toDateString()) {
            return collect();
        }

        $total = (int) $latestPr->squat + (int) $latestPr->bench + (int) $latestPr->deadlift;

        return collect([
            $this->makeAlert(
                key: "pr-celebration-{$athlete->id}-{$latestPr->reference_date->toDateString()}",
                type: 'pr_celebration',
                severity: 'info',
                title: 'Nouveau PR enregistré',
                body: "{$athlete->name} a mis à jour ses records : "
                    ."S {$latestPr->squat} · B {$latestPr->bench} · T {$latestPr->deadlift} (total {$total}).",
                href: "/athletes/{$athlete->id}",
                athleteId: $athlete->id,
                athleteName: $athlete->name,
                sortDate: $latestPr->reference_date->toDateString(),
                sharePayload: $this->buildSharePayload(
                    variant: 'pr_celebration',
                    athleteName: $athlete->name,
                    date: $latestPr->reference_date->toDateString(),
                    headline: "Nouveau total {$total} kg",
                    subline: "S {$latestPr->squat} · B {$latestPr->bench} · T {$latestPr->deadlift}",
                    metrics: [
                        'squat' => (int) $latestPr->squat,
                        'bench' => (int) $latestPr->bench,
                        'deadlift' => (int) $latestPr->deadlift,
                        'total' => $total,
                    ],
                ),
            ),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function inactivityAlerts(
        User $athlete,
        AthleteProgramAssignment $assignment,
        Carbon $today,
        ?string $latestSessionDate,
    ): Collection {
        if ($latestSessionDate === null) {
            return collect();
        }

        $daysSince = Carbon::parse($latestSessionDate)->startOfDay()->diffInDays($today);

        if ($daysSince < self::INACTIVE_DAYS) {
            return collect();
        }

        return collect([
            $this->makeAlert(
                key: "inactive-{$athlete->id}",
                type: 'inactive_athlete',
                severity: 'warning',
                title: 'Aucune séance récente',
                body: "{$athlete->name} n'a pas enregistré de séance depuis {$daysSince} jours "
                    .'malgré un programme actif.',
                href: "/athletes/{$athlete->id}",
                athleteId: $athlete->id,
                athleteName: $athlete->name,
                sortDate: $latestSessionDate,
            ),
        ]);
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function unreadMessageAlerts(User $coach): Collection
    {
        return MessageThread::query()
            ->where('coach_id', $coach->id)
            ->withUnreadCountFor($coach)
            ->with('athlete:id,name')
            ->get()
            ->filter(fn (MessageThread $thread) => (int) $thread->unread_messages_count > 0)
            ->sortByDesc('unread_messages_count')
            ->values()
            ->map(function (MessageThread $thread): array {
                $count = (int) $thread->unread_messages_count;
                $athleteName = $thread->athlete?->name ?? 'Athlète';

                return $this->makeAlert(
                    key: "unread-thread-{$thread->id}",
                    type: 'unread_message',
                    severity: 'info',
                    title: 'Message non lu',
                    body: "{$count} message".($count > 1 ? 's' : '')." non lu".($count > 1 ? 's' : '')
                        ." de {$athleteName}.",
                    href: "/messaging?thread={$thread->id}",
                    athleteId: $thread->athlete_id,
                    athleteName: $athleteName,
                    sortDate: now()->toDateString(),
                );
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $alerts
     * @return Collection<int, array<string, mixed>>
     */
    private function limitAlertsPerType(Collection $alerts): Collection
    {
        return $alerts
            ->groupBy('type')
            ->flatMap(fn (Collection $group) => $group->take(self::MAX_ALERTS_PER_TYPE))
            ->pipe(fn (Collection $limited) => $this->sortAlerts($limited));
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $alerts
     * @return Collection<int, array<string, mixed>>
     */
    private function sortAlerts(Collection $alerts): Collection
    {
        $severityWeight = [
            'critical' => 3,
            'warning' => 2,
            'info' => 1,
        ];

        return $alerts->sortByDesc(function (array $alert) use ($severityWeight): array {
            return [
                $severityWeight[$alert['severity']] ?? 0,
                -1 * strtotime((string) ($alert['sort_date'] ?? '1970-01-01')),
            ];
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function makeAlert(
        string $key,
        string $type,
        string $severity,
        string $title,
        string $body,
        string $href,
        ?int $athleteId,
        ?string $athleteName,
        string $sortDate,
        ?array $sharePayload = null,
    ): array {
        return [
            'key' => $key,
            'type' => $type,
            'severity' => $severity,
            'title' => $title,
            'body' => $body,
            'href' => $href,
            'athlete_id' => $athleteId,
            'athlete_name' => $athleteName,
            'sort_date' => $sortDate,
            'share_payload' => $sharePayload,
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array<string, mixed>
     */
    private function buildSharePayload(
        string $variant,
        string $athleteName,
        string $date,
        string $headline,
        string $subline,
        array $metrics,
    ): array {
        return [
            'variant' => $variant,
            'athlete_name' => $athleteName,
            'date' => $date,
            'headline' => $headline,
            'subline' => $subline,
            'metrics' => $metrics,
            'social_text' => "{$athleteName} · {$headline} sur Power Roster",
            'share_url' => '/dashboard',
            'templates' => [
                ['id' => 'block_recap', 'label' => 'Fin de bloc'],
                ['id' => 'weekly_recap', 'label' => 'Récap hebdomadaire'],
                ['id' => 'meet_day', 'label' => 'Compétition'],
                ['id' => 'checkin_streak', 'label' => 'Streak check-in'],
            ],
        ];
    }
}
