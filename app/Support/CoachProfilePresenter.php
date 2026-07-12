<?php

namespace App\Support;

use App\Models\CoachProfile;
use App\Models\User;

class CoachProfilePresenter
{
    /**
     * @return array<string, mixed>
     */
    public static function forCoach(User $coach, bool $includeStats = false): array
    {
        $profile = $coach->coachProfile;

        $payload = [
            'id' => $coach->id,
            'name' => $coach->name,
            'email' => $coach->email,
            'bio' => $profile?->bio,
            'avatar_url' => $profile?->avatar_path ? asset('storage/'.$profile->avatar_path) : null,
            'specialties' => $profile?->specialties ?? [],
            'specialty_labels' => collect($profile?->specialties ?? [])
                ->map(fn (string $key) => IpfWeightCategorySupport::specialtyLabels()[$key] ?? $key)
                ->values()
                ->all(),
            'years_experience' => $profile?->years_experience,
            'certifications' => $profile?->certifications,
            'club_gym' => $profile?->club_gym,
        ];

        if ($includeStats) {
            $payload['roster_stats'] = CoachRosterStatsSupport::forCoach($coach);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public static function editableFields(?CoachProfile $profile): array
    {
        return [
            'bio' => $profile?->bio ?? '',
            'specialties' => $profile?->specialties ?? [],
            'years_experience' => $profile?->years_experience,
            'certifications' => $profile?->certifications ?? '',
            'club_gym' => $profile?->club_gym ?? '',
        ];
    }
}
