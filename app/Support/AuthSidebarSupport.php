<?php

namespace App\Support;

use App\Models\User;

class AuthSidebarSupport
{
    /**
     * @return array<string, mixed>|null
     */
    public static function coachSummaryForAthlete(User $athlete): ?array
    {
        if ($athlete->role !== 'athlete') {
            return null;
        }

        $coach = $athlete->primaryCoach();
        if ($coach === null) {
            return null;
        }

        $coach->loadMissing('coachProfile');

        return [
            'id' => $coach->id,
            'name' => $coach->name,
            'club_gym' => $coach->coachProfile?->club_gym,
            'profile_url' => route('coaches.show', $coach),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function profileLinkForUser(User $user): ?array
    {
        if ($user->role === 'coach') {
            return [
                'label' => $user->name,
                'subtitle' => 'Mon profil & stats roster',
                'href' => route('coach.profile'),
            ];
        }

        $coach = self::coachSummaryForAthlete($user);
        if ($coach === null) {
            return [
                'label' => $user->name,
                'subtitle' => $user->email,
                'href' => route('athletes.show', $user),
            ];
        }

        return [
            'label' => 'Mon coach — '.$coach['name'],
            'subtitle' => $coach['club_gym'] ?? 'Voir le profil',
            'href' => $coach['profile_url'],
        ];
    }
}
