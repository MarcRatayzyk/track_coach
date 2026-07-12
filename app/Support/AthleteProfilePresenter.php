<?php

namespace App\Support;

use App\Models\AthleteProfile;

class AthleteProfilePresenter
{
    /**
     * @return array<string, mixed>|null
     */
    public static function forProfile(?AthleteProfile $profile): ?array
    {
        if ($profile === null) {
            return null;
        }

        return [
            'birth_date' => $profile->birth_date?->toDateString(),
            'height_cm' => $profile->height_cm,
            'sex' => $profile->sex,
            'weight_category' => $profile->weight_category,
            'weight_category_label' => IpfWeightCategorySupport::labelForCategory($profile->weight_category),
            'level' => $profile->level,
            'level_label' => IpfWeightCategorySupport::labelForLevel($profile->level),
            'injuries_notes' => $profile->injuries_notes,
            'bio' => $profile->bio,
            'profession' => $profile->profession,
            'years_training' => $profile->years_training,
            'feedback_frequency' => $profile->feedback_frequency,
        ];
    }
}
