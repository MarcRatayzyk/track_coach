<?php

namespace App\Support;

class GlPointsCalculator
{
    /** Coefficients IPF GL (homme, classique, barre). */
    private const MALE_CLASSIC = [
        'a' => 1199.72839,
        'b' => 1025.18162,
        'c' => 0.00921,
    ];

    /** Coefficients IPF GL (femme, classique, barre). */
    private const FEMALE_CLASSIC = [
        'a' => 610.32796,
        'b' => 1045.59282,
        'c' => 0.03048,
    ];

    public static function calculate(int $totalKg, ?float $bodyweightKg, string $sex = 'male'): ?float
    {
        if ($totalKg <= 0 || $bodyweightKg === null || $bodyweightKg <= 0) {
            return null;
        }

        $coefficients = $sex === 'female' ? self::FEMALE_CLASSIC : self::MALE_CLASSIC;
        $denominator = $coefficients['a']
            - ($coefficients['b'] * exp(-$coefficients['c'] * $bodyweightKg));

        if ($denominator <= 0) {
            return null;
        }

        return round(($totalKg * 100) / $denominator, 2);
    }

    public static function bodyweightFromClass(?string $weightClass, ?string $sex = null): ?float
    {
        if ($weightClass === null || $weightClass === '') {
            return null;
        }

        $categoryBodyweights = [
            'm59' => 59.0, 'm66' => 66.0, 'm74' => 74.0, 'm83' => 83.0,
            'm93' => 93.0, 'm105' => 105.0, 'm120' => 120.0, 'm120plus' => 125.0,
            'f47' => 47.0, 'f52' => 52.0, 'f57' => 57.0, 'f63' => 63.0,
            'f69' => 69.0, 'f76' => 76.0, 'f84' => 84.0, 'f84plus' => 90.0,
        ];

        if (isset($categoryBodyweights[$weightClass])) {
            return $categoryBodyweights[$weightClass];
        }

        if (preg_match('/(\d+(?:[.,]\d+)?)/', $weightClass, $matches) !== 1) {
            return null;
        }

        $value = (float) str_replace(',', '.', $matches[1]);

        return $value > 0 ? $value : null;
    }

    public static function sexFromCategory(?string $weightCategory): string
    {
        if ($weightCategory !== null && str_starts_with($weightCategory, 'f')) {
            return 'female';
        }

        return 'male';
    }
}
