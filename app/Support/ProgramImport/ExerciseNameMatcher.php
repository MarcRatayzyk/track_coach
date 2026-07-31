<?php

namespace App\Support\ProgramImport;

use App\Models\Exercise;
use App\Models\User;
use Illuminate\Support\Collection;

class ExerciseNameMatcher
{
    /** @var list<array{exercise_variant_id: int, exercise_name: string, lift: string|null, normalized: string}> */
    private array $catalog = [];

    public function __construct(?User $coach = null)
    {
        if ($coach === null) {
            return;
        }

        /** @var Collection<int, Exercise> $exercises */
        $exercises = Exercise::query()
            ->forCoach($coach)
            ->with('variants')
            ->get();

        foreach ($exercises as $exercise) {
            $lift = in_array($exercise->lift, ['squat', 'bench', 'deadlift'], true)
                ? $exercise->lift
                : null;

            foreach ($exercise->variants as $variant) {
                $name = trim((string) $variant->name);
                if ($name === '') {
                    continue;
                }

                $this->catalog[] = [
                    'exercise_variant_id' => (int) $variant->id,
                    'exercise_name' => $name,
                    'lift' => $lift,
                    'normalized' => $this->normalize($name),
                ];
            }

            $baseName = trim((string) $exercise->name);
            if ($baseName !== '' && $exercise->variants->isEmpty()) {
                $this->catalog[] = [
                    'exercise_variant_id' => 0,
                    'exercise_name' => $baseName,
                    'lift' => $lift,
                    'normalized' => $this->normalize($baseName),
                ];
            }
        }
    }

    /**
     * @return array{exercise_variant_id: int|null, exercise_name: string, lift: string|null, matched: bool, score: float}
     */
    public function match(string $rawName): array
    {
        $name = trim($rawName);
        if ($name === '') {
            return [
                'exercise_variant_id' => null,
                'exercise_name' => '',
                'lift' => null,
                'matched' => false,
                'score' => 0.0,
            ];
        }

        $normalized = $this->normalize($name);
        $best = null;
        $bestScore = 0.0;

        foreach ($this->catalog as $entry) {
            if ($entry['normalized'] === $normalized) {
                return [
                    'exercise_variant_id' => $entry['exercise_variant_id'] > 0 ? $entry['exercise_variant_id'] : null,
                    'exercise_name' => $entry['exercise_name'],
                    'lift' => $entry['lift'],
                    'matched' => true,
                    'score' => 1.0,
                ];
            }

            $score = $this->similarity($normalized, $entry['normalized']);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $entry;
            }
        }

        if ($best !== null && $bestScore >= 0.82) {
            return [
                'exercise_variant_id' => $best['exercise_variant_id'] > 0 ? $best['exercise_variant_id'] : null,
                'exercise_name' => $best['exercise_name'],
                'lift' => $best['lift'],
                'matched' => true,
                'score' => $bestScore,
            ];
        }

        return [
            'exercise_variant_id' => null,
            'exercise_name' => $name,
            'lift' => null,
            'matched' => false,
            'score' => $bestScore,
        ];
    }

    public function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = strtr($value, [
            'à' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'î' => 'i', 'ï' => 'i',
            'ô' => 'o', 'ö' => 'o',
            'ù' => 'u', 'û' => 'u', 'ü' => 'u',
            'ç' => 'c',
        ]);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function similarity(string $a, string $b): float
    {
        if ($a === '' || $b === '') {
            return 0.0;
        }

        if (str_contains($a, $b) || str_contains($b, $a)) {
            $shorter = min(mb_strlen($a), mb_strlen($b));
            $longer = max(mb_strlen($a), mb_strlen($b));

            return $longer > 0 ? ($shorter / $longer) * 0.95 : 0.0;
        }

        similar_text($a, $b, $percent);

        return $percent / 100;
    }
}
