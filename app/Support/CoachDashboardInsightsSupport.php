<?php

namespace App\Support;

use App\Models\AthleteProgramAssignment;
use App\Models\Competition;
use App\Models\Message;
use App\Models\MessageThread;
use App\Models\PersonalRecord;
use App\Models\SessionFeedback;
use App\Models\User;
use App\Services\AthleteSessionCoverageService;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CoachDashboardInsightsSupport
{
    /**
     * @param  Collection<int, int|string>  $athleteIds
     * @return array{
     *     feed: list<array<string, mixed>>,
     *     performance: array<string, mixed>,
     * }
     */
    public static function forCoach(User $coach, Collection $athleteIds): array
    {
        return [
            'feed' => self::activityFeed($coach, $athleteIds),
            'performance' => self::performance($coach, $athleteIds),
        ];
    }

    /**
     * @param  Collection<int, int|string>  $athleteIds
     * @return list<array<string, mixed>>
     */
    private static function activityFeed(User $coach, Collection $athleteIds): array
    {
        $events = collect();

        SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereIn('athlete_id', $athleteIds)
            ->with('athlete:id,name')
            ->orderByDesc('submitted_at')
            ->limit(8)
            ->get()
            ->each(function (SessionFeedback $feedback) use ($events): void {
                if ($feedback->submitted_at === null) {
                    return;
                }
                $events->push([
                    'id' => 'feedback-'.$feedback->id,
                    'type' => 'feedback_received',
                    'title' => 'Retour reçu',
                    'body' => $feedback->athlete?->name ?? 'Athlète',
                    'athlete_name' => $feedback->athlete?->name,
                    'athlete_id' => $feedback->athlete_id,
                    'href' => '/feedbacks?feedback='.$feedback->id.'&filter=pending',
                    'occurred_at' => $feedback->submitted_at->toIso8601String(),
                    'color' => 'amber',
                    'icon' => 'video',
                ]);
            });

        PersonalRecord::query()
            ->whereIn('athlete_id', $athleteIds)
            ->with('athlete:id,name')
            ->orderByDesc('reference_date')
            ->orderByDesc('id')
            ->limit(6)
            ->get()
            ->each(function (PersonalRecord $pr) use ($events): void {
                $date = $pr->reference_date ?? $pr->updated_at;
                if ($date === null) {
                    return;
                }
                $events->push([
                    'id' => 'pr-'.$pr->id,
                    'type' => 'new_pr',
                    'title' => 'Nouveau PR',
                    'body' => $pr->athlete?->name ?? 'Athlète',
                    'athlete_name' => $pr->athlete?->name,
                    'athlete_id' => $pr->athlete_id,
                    'href' => '/athletes/'.$pr->athlete_id,
                    'occurred_at' => Carbon::parse($date)->startOfDay()->toIso8601String(),
                    'color' => 'emerald',
                    'icon' => 'trophy',
                ]);
            });

        Competition::query()
            ->whereIn('athlete_id', $athleteIds)
            ->with('athlete:id,name')
            ->orderByDesc('created_at')
            ->limit(6)
            ->get()
            ->each(function (Competition $competition) use ($events): void {
                $at = $competition->created_at ?? $competition->competition_date;
                if ($at === null) {
                    return;
                }
                $events->push([
                    'id' => 'comp-'.$competition->id,
                    'type' => 'competition_added',
                    'title' => 'Compétition ajoutée',
                    'body' => ($competition->name ?: 'Compétition').' · '.($competition->athlete?->name ?? ''),
                    'athlete_name' => $competition->athlete?->name,
                    'athlete_id' => $competition->athlete_id,
                    'href' => '/competitions',
                    'occurred_at' => Carbon::parse($at)->toIso8601String(),
                    'color' => 'rose',
                    'icon' => 'calendar',
                ]);
            });

        AthleteProgramAssignment::query()
            ->whereIn('athlete_id', $athleteIds)
            ->with(['athlete:id,name', 'template:id,name'])
            ->orderByDesc('updated_at')
            ->limit(6)
            ->get()
            ->each(function (AthleteProgramAssignment $assignment) use ($events): void {
                if ($assignment->updated_at === null) {
                    return;
                }
                $label = $assignment->template?->name ?? 'Programme';
                $events->push([
                    'id' => 'program-'.$assignment->id.'-'.$assignment->updated_at->timestamp,
                    'type' => 'program_updated',
                    'title' => 'Programme modifié',
                    'body' => $label.' · '.($assignment->athlete?->name ?? ''),
                    'athlete_name' => $assignment->athlete?->name,
                    'athlete_id' => $assignment->athlete_id,
                    'href' => '/athletes/'.$assignment->athlete_id,
                    'occurred_at' => $assignment->updated_at->toIso8601String(),
                    'color' => 'violet',
                    'icon' => 'bolt',
                ]);
            });

        $threadIds = MessageThread::query()
            ->where('coach_id', $coach->id)
            ->pluck('id');

        if ($threadIds->isNotEmpty()) {
            Message::query()
                ->whereIn('thread_id', $threadIds)
                ->where('sender_id', '!=', $coach->id)
                ->with(['sender:id,name', 'thread:id,athlete_id'])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get()
                ->each(function (Message $message) use ($events): void {
                    if ($message->created_at === null) {
                        return;
                    }
                    $events->push([
                        'id' => 'msg-'.$message->id,
                        'type' => 'message_received',
                        'title' => 'Message reçu',
                        'body' => $message->sender?->name ?? 'Athlète',
                        'athlete_name' => $message->sender?->name,
                        'athlete_id' => $message->thread?->athlete_id,
                        'href' => '/messaging?thread='.$message->thread_id,
                        'occurred_at' => $message->created_at->toIso8601String(),
                        'color' => 'blue',
                        'icon' => 'chat',
                    ]);
                });
        }

        $coach->athletes()
            ->where('users.role', 'athlete')
            ->wherePivot('status', 'active')
            ->orderByDesc('coach_athlete.created_at')
            ->limit(6)
            ->get(['users.id', 'users.name'])
            ->each(function (User $athlete) use ($events): void {
                $joinedAt = $athlete->pivot?->created_at;
                if ($joinedAt === null) {
                    return;
                }
                $events->push([
                    'id' => 'join-'.$athlete->id,
                    'type' => 'athlete_connected',
                    'title' => 'Connexion d’un athlète',
                    'body' => $athlete->name,
                    'athlete_name' => $athlete->name,
                    'athlete_id' => $athlete->id,
                    'href' => '/athletes/'.$athlete->id,
                    'occurred_at' => Carbon::parse($joinedAt)->toIso8601String(),
                    'color' => 'sky',
                    'icon' => 'users',
                ]);
            });

        return $events
            ->sortByDesc(fn (array $event) => $event['occurred_at'] ?? '')
            ->unique('id')
            ->take(12)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int|string>  $athleteIds
     * @return array<string, mixed>
     */
    private static function performance(User $coach, Collection $athleteIds): array
    {
        $today = now()->copy()->startOfDay();
        $weekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
        $periodStart = $today->copy()->subDays(6);

        $rosterStats = CoachRosterStatsSupport::forCoach($coach);

        $activeAthletes = MessageThread::query()
            ->where('coach_id', $coach->id)
            ->where('updated_at', '>=', $today->copy()->subDays(7))
            ->count();

        $prsThisWeek = PersonalRecord::query()
            ->whereIn('athlete_id', $athleteIds)
            ->whereDate('reference_date', '>=', $weekStart->toDateString())
            ->whereDate('reference_date', '<=', $today->toDateString())
            ->count();

        $feedbackByDay = SessionFeedback::query()
            ->where('coach_id', $coach->id)
            ->whereDate('submitted_at', '>=', $today->copy()->subDays(27)->toDateString())
            ->whereDate('submitted_at', '<=', $today->toDateString())
            ->get(['submitted_at'])
            ->groupBy(fn (SessionFeedback $feedback) => $feedback->submitted_at?->toDateString())
            ->map->count();

        $heatmap = [];
        for ($d = 27; $d >= 0; $d--) {
            $day = $today->copy()->subDays($d);
            $key = $day->toDateString();
            $heatmap[] = [
                'date' => $key,
                'value' => (int) ($feedbackByDay[$key] ?? 0),
            ];
        }

        $feedbackSeries = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = $today->copy()->subDays($i);
            $key = $day->toDateString();
            $feedbackSeries[] = [
                'date' => $key,
                'label' => $day->locale(app()->getLocale())->isoFormat('dd'),
                'value' => (int) ($feedbackByDay[$key] ?? 0),
            ];
        }

        $adherenceSeries = [];
        $coverageService = app(AthleteSessionCoverageService::class);
        for ($i = 3; $i >= 0; $i--) {
            $end = $today->copy()->subWeeks($i);
            $start = $end->copy()->subDays(6);
            $values = [];

            foreach ($athleteIds as $athleteId) {
                $assignment = AthleteProgramAssignment::query()
                    ->where('athlete_id', $athleteId)
                    ->where('status', 'active')
                    ->whereDate('date_start', '<=', $end->toDateString())
                    ->whereDate('date_end', '>=', $start->toDateString())
                    ->latest('date_start')
                    ->first();

                if ($assignment === null) {
                    continue;
                }

                $coverage = $coverageService->coverageBetween(
                    (int) $athleteId,
                    $assignment,
                    $start,
                    $end,
                );

                if ($coverage['percentage'] !== null) {
                    $values[] = $coverage['percentage'];
                }
            }

            $adherenceSeries[] = [
                'label' => 'S-'.$i,
                'value' => count($values) > 0 ? (int) round(array_sum($values) / count($values)) : 0,
            ];
        }

        $expectedDaily = max(1, (int) ($rosterStats['athlete_count'] ?? 1));
        $filledToday = (int) ($feedbackByDay[$today->toDateString()] ?? 0);

        return [
            'average_adherence' => $rosterStats['average_adherence_30d'],
            'prs_this_week' => $prsThisWeek,
            'feedback_fill_rate' => min(100, (int) round(($filledToday / $expectedDaily) * 100)),
            'active_athletes_7d' => $activeAthletes,
            'athlete_count' => (int) ($rosterStats['athlete_count'] ?? 0),
            'active_blocks' => (int) ($rosterStats['active_blocks'] ?? 0),
            'volume_proxy' => (int) round(collect($feedbackSeries)->avg('value') * 10),
            'feedback_series' => $feedbackSeries,
            'adherence_series' => $adherenceSeries,
            'heatmap' => $heatmap,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $today->toDateString(),
        ];
    }
}
