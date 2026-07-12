<?php

namespace App\Support;

class IpfWeightCategorySupport
{
    public const SEX_MALE = 'male';

    public const SEX_FEMALE = 'female';

    /** @var list<string> */
    public const LEVELS = ['beginner', 'intermediate', 'advanced', 'elite'];

    /** @var list<string> */
    public const MALE_CATEGORIES = [
        'm59', 'm66', 'm74', 'm83', 'm93', 'm105', 'm120', 'm120plus',
    ];

    /** @var list<string> */
    public const FEMALE_CATEGORIES = [
        'f47', 'f52', 'f57', 'f63', 'f69', 'f76', 'f84', 'f84plus',
    ];

    /**
     * @return list<string>
     */
    public static function categoriesForSex(?string $sex): array
    {
        return match ($sex) {
            self::SEX_MALE => self::MALE_CATEGORIES,
            self::SEX_FEMALE => self::FEMALE_CATEGORIES,
            default => array_merge(self::MALE_CATEGORIES, self::FEMALE_CATEGORIES),
        };
    }

    /**
     * @return list<string>
     */
    public static function allCategories(): array
    {
        return array_merge(self::MALE_CATEGORIES, self::FEMALE_CATEGORIES);
    }

    public static function labelForCategory(?string $category): ?string
    {
        if ($category === null) {
            return null;
        }

        return self::labels()[$category] ?? $category;
    }

    public static function labelForLevel(?string $level): ?string
    {
        if ($level === null) {
            return null;
        }

        return self::levelLabels()[$level] ?? $level;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            'm59' => '59 kg (H)',
            'm66' => '66 kg (H)',
            'm74' => '74 kg (H)',
            'm83' => '83 kg (H)',
            'm93' => '93 kg (H)',
            'm105' => '105 kg (H)',
            'm120' => '120 kg (H)',
            'm120plus' => '120+ kg (H)',
            'f47' => '47 kg (F)',
            'f52' => '52 kg (F)',
            'f57' => '57 kg (F)',
            'f63' => '63 kg (F)',
            'f69' => '69 kg (F)',
            'f76' => '76 kg (F)',
            'f84' => '84 kg (F)',
            'f84plus' => '84+ kg (F)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function levelLabels(): array
    {
        return [
            'beginner' => 'Débutant',
            'intermediate' => 'Intermédiaire',
            'advanced' => 'Avancé',
            'elite' => 'Élite',
        ];
    }

    /**
     * @return list<string>
     */
    public static function specialtyOptions(): array
    {
        return [
            'powerlifting',
            'force_athletique',
            'hypertrophie',
            'preparation_physique',
            'rehabilitation',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function specialtyLabels(): array
    {
        return [
            'powerlifting' => 'Powerlifting',
            'force_athletique' => 'Force athlétique',
            'hypertrophie' => 'Hypertrophie',
            'preparation_physique' => 'Préparation physique',
            'rehabilitation' => 'Réhabilitation / retour',
        ];
    }
}
