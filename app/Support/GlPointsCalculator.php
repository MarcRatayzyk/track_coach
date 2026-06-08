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

    public static function bodyweightFromClass(?string $weightClass): ?float
    {
        if ($weightClass === null || $weightClass === '') {
            return null;
        }

        if (preg_match('/(\d+(?:[.,]\d+)?)/', $weightClass, $matches) !== 1) {
            return null;
        }

        $value = (float) str_replace(',', '.', $matches[1]);

        return $value > 0 ? $value : null;
    }
}
