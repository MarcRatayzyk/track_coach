<?php

namespace App\Support;

use App\Models\MessageThread;
use App\Models\User;

class MessagingInboxSupport
{
    public static function activeCoachForAthlete(User $athlete): ?User
    {
        return $athlete->coaches()
            ->wherePivot('status', 'active')
            ->orderBy('coach_athlete.created_at')
            ->first();
    }

    public static function threadForAthlete(User $athlete): ?MessageThread
    {
        $coach = self::activeCoachForAthlete($athlete);

        if ($coach === null) {
            return null;
        }

        return MessageThread::query()->firstOrCreate([
            'coach_id' => $coach->id,
            'athlete_id' => $athlete->id,
        ]);
    }

    public static function unreadCountFor(User $user, MessageThread $thread): int
    {
        return (int) $thread->messages()
            ->whereNull('read_at')
            ->where('sender_id', '!=', $user->id)
            ->count();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function athleteInboxSummary(User $athlete): ?array
    {
        $thread = self::threadForAthlete($athlete);

        if ($thread === null) {
            return null;
        }

        $thread->loadMissing('coach:id,name');

        return [
            'thread_id' => $thread->id,
            'unread_count' => self::unreadCountFor($athlete, $thread),
            'coach_name' => $thread->coach?->name,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function threadListItem(MessageThread $thread, User $viewer): array
    {
        $thread->loadMissing(['athlete:id,name', 'coach:id,name']);
        $thread->loadCount([
            'messages as unread_messages_count' => fn ($query) => $query
                ->whereNull('read_at')
                ->where('sender_id', '!=', $viewer->id),
        ]);

        return [
            'id' => $thread->id,
            'athlete' => $thread->athlete ? [
                'id' => $thread->athlete->id,
                'name' => $thread->athlete->name,
            ] : null,
            'coach' => $thread->coach ? [
                'id' => $thread->coach->id,
                'name' => $thread->coach->name,
            ] : null,
            'messages_count' => $thread->messages_count ?? $thread->messages()->count(),
            'unread_messages_count' => (int) ($thread->unread_messages_count ?? 0),
            'updated_at' => $thread->updated_at?->toIso8601String(),
        ];
    }
}
