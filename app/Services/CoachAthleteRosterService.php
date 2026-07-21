<?php

namespace App\Services;

use App\Models\AthleteProgramAssignment;
use App\Models\AthleteReadinessEntry;
use App\Models\MessageThread;
use App\Models\User;
use App\Support\GlPointsCalculator;
use App\Support\IpfWeightCategorySupport;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CoachAthleteRosterService
{
    public function __construct(
        private readonly AthleteSessionCoverageService $sessionCoverage,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function rowsForCoach(User $coach): array
    {
        $today = now()->copy()->startOfDay();
        $readinessStart = $today->copy()->subDays(6);

        $athletes = $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->with([
                'profile',
                'latestPr',
                'upcomingCompetition',
                'programAssignments' => fn ($query) => $query
                    ->where('status', 'active')
                    ->with('template:id,name'),
            ])
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.email', 'users.initial_setup_completed_at']);

        if ($athletes->isEmpty()) {
            return [];
        }

        $athleteIds = $athletes->pluck('id');

        $readinessByAthlete = AthleteReadinessEntry::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('entry_date', '>=', $readinessStart->toDateString())
            ->whereDate('entry_date', '<=', $today->toDateString())
            ->get()
            ->groupBy('athlete_id');

        $threadsByAthlete = MessageThread::query()
            ->where('coach_id', $coach->id)
            ->whereIn('athlete_id', $athleteIds)
            ->withUnreadCountFor($coach)
            ->get()
            ->keyBy('athlete_id');

        return $athletes
            ->map(function (User $athlete) use (
                $today,
                $readinessByAthlete,
                $threadsByAthlete,
            ): array {
                $pr = $athlete->latestPr;
                $squat = (int) ($pr?->squat ?? 0);
                $bench = (int) ($pr?->bench ?? 0);
                $deadlift = (int) ($pr?->deadlift ?? 0);
                $total = $squat + $bench + $deadlift;

                $weightCategory = $athlete->profile?->weight_category;
                $sex = $athlete->profile?->sex ?? GlPointsCalculator::sexFromCategory($weightCategory);
                $bodyweight = GlPointsCalculator::bodyweightFromClass($weightCategory, $sex);
                $glPoints = GlPointsCalculator::calculate($total, $bodyweight, $sex);

                $entries = $readinessByAthlete->get($athlete->id, collect());
                $readinessEntriesCount = $entries->count();

                $assignment = $athlete->programAssignments->first();
                $adherence = $this->adherenceForAssignment($athlete->id, $assignment, $today);

                $thread = $threadsByAthlete->get($athlete->id);
                $nextCompetition = $athlete->upcomingCompetition;
                $nextCompetitionDays = null;

                if ($nextCompetition?->competition_date !== null) {
                    $competitionDate = Carbon::parse($nextCompetition->competition_date)->startOfDay();
                    $nextCompetitionDays = (int) max(0, $today->diffInDays($competitionDate, false));
                }

                return [
                    'id' => $athlete->id,
                    'name' => $athlete->name,
                    'email' => $athlete->email,
                    'is_pending_activation' => $athlete->initial_setup_completed_at === null,
                    'weight_category' => $weightCategory,
                    'weight_category_label' => IpfWeightCategorySupport::labelForCategory($weightCategory),
                    'total_kg' => $total > 0 ? $total : null,
                    'gl_points' => $glPoints,
                    'readiness_entries_count' => $readinessEntriesCount,
                    'readiness_checkins_7d' => $readinessEntriesCount,
                    'adherence_percentage' => $adherence,
                    'next_competition_days' => $nextCompetitionDays,
                    'next_competition_name' => $nextCompetition?->name,
                    'next_competition_date' => $nextCompetition?->competition_date?->toDateString(),
                    'active_program_assignment_id' => $assignment?->id,
                    'message_thread_id' => $thread?->id,
                    'unread_messages_count' => (int) ($thread?->unread_messages_count ?? 0),
                ];
            })
            ->values()
            ->all();
    }

    private function adherenceForAssignment(
        int $athleteId,
        ?AthleteProgramAssignment $assignment,
        Carbon $today,
    ): ?int {
        if ($assignment === null) {
            return null;
        }

        $start = $today->copy()->subDays(6);
        $coverage = $this->sessionCoverage->coverageBetween(
            $athleteId,
            $assignment,
            $start,
            $today,
        );

        return $coverage['percentage'];
    }
}
