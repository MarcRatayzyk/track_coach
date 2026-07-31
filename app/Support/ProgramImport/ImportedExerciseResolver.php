<?php

namespace App\Support\ProgramImport;

use App\Models\Exercise;
use App\Models\ExerciseVariant;
use App\Models\User;
use Illuminate\Support\Str;

class ImportedExerciseResolver
{
    public function __construct(
        private readonly User $coach,
        private ExerciseNameMatcher $matcher,
    ) {}

    /**
     * @return array{
     *   exercise_variant_id: int|null,
     *   exercise_name: string,
     *   lift: string|null,
     *   matched: bool,
     *   will_create: bool,
     *   parent_name: string|null,
     *   variant_name: string,
     *   category: string
     * }
     */
    public function resolve(string $rawName, ?string $parentName = null, ?string $lift = null, ?string $category = null): array
    {
        $variantName = trim($rawName);
        $parent = trim((string) $parentName);
        $normalizedLift = $this->normalizeLift($lift) ?? $this->inferLift($variantName, $parent);
        $normalizedCategory = $this->normalizeCategory($category, $normalizedLift, $variantName);

        if ($variantName === '') {
            return [
                'exercise_variant_id' => null,
                'exercise_name' => '',
                'lift' => $normalizedLift,
                'matched' => false,
                'will_create' => false,
                'parent_name' => $parent !== '' ? $parent : null,
                'variant_name' => '',
                'category' => $normalizedCategory,
            ];
        }

        $match = $this->matcher->match($variantName);
        if ($match['matched'] && $match['exercise_variant_id']) {
            return [
                'exercise_variant_id' => $match['exercise_variant_id'],
                'exercise_name' => $match['exercise_name'],
                'lift' => $match['lift'] ?? $normalizedLift,
                'matched' => true,
                'will_create' => false,
                'parent_name' => $parent !== '' ? $parent : null,
                'variant_name' => $match['exercise_name'],
                'category' => $normalizedCategory,
            ];
        }

        if ($parent !== '') {
            $parentMatch = $this->matcher->match($parent);
            // Parent found but variant missing → still will create variant under that parent on apply.
        }

        $defaultParent = $parent !== '' ? $parent : $this->defaultParentName($normalizedLift, $normalizedCategory, $variantName);

        return [
            'exercise_variant_id' => null,
            'exercise_name' => $variantName,
            'lift' => $normalizedLift,
            'matched' => false,
            'will_create' => true,
            'parent_name' => $defaultParent,
            'variant_name' => $variantName,
            'category' => $normalizedCategory,
        ];
    }

    /**
     * @param  list<array{parent_name: string, variant_name: string, lift: string|null, category: string}>  $pending
     * @return array<string, int> map "parent|variant" => exercise_variant_id
     */
    public function createPending(array $pending): array
    {
        $created = [];

        foreach ($pending as $item) {
            $parentName = trim((string) ($item['parent_name'] ?? ''));
            $variantName = trim((string) ($item['variant_name'] ?? ''));
            if ($parentName === '' || $variantName === '') {
                continue;
            }

            $key = mb_strtolower($parentName).'|'.mb_strtolower($variantName);
            if (isset($created[$key])) {
                continue;
            }

            $lift = $this->normalizeLift($item['lift'] ?? null) ?? Exercise::LIFT_GENERAL;
            $category = $this->normalizeCategory($item['category'] ?? null, $lift, $variantName);

            $exercise = $this->findOrCreateParent($parentName, $lift, $category);
            $variant = $this->findOrCreateVariant($exercise, $variantName);
            $created[$key] = (int) $variant->id;
        }

        // Refresh matcher catalog for subsequent matches in same request.
        $this->matcher = new ExerciseNameMatcher($this->coach);

        return $created;
    }

    private function findOrCreateParent(string $parentName, string $lift, string $category): Exercise
    {
        $existing = Exercise::query()
            ->forCoach($this->coach)
            ->where(function ($q) use ($parentName): void {
                $q->whereRaw('lower(name) = ?', [mb_strtolower($parentName)]);
            })
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        // Prefer global catalog parents by lift for main lifts.
        if (in_array($lift, [Exercise::LIFT_SQUAT, Exercise::LIFT_BENCH, Exercise::LIFT_DEADLIFT], true)
            && $category === Exercise::CATEGORY_MAIN_LIFT) {
            $byLift = Exercise::query()
                ->whereNull('coach_id')
                ->where('lift', $lift)
                ->where('category', Exercise::CATEGORY_MAIN_LIFT)
                ->orderBy('id')
                ->first();
            if ($byLift !== null) {
                return $byLift;
            }
        }

        return Exercise::query()->create([
            'coach_id' => $this->coach->id,
            'is_custom' => true,
            'name' => $parentName,
            'slug' => $this->uniqueExerciseSlug($parentName),
            'lift' => $lift,
            'category' => $category,
            'equipment' => 'other',
            'movement_pattern' => null,
        ]);
    }

    private function findOrCreateVariant(Exercise $exercise, string $variantName): ExerciseVariant
    {
        $existing = $exercise->variants()
            ->whereRaw('lower(name) = ?', [mb_strtolower($variantName)])
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return $exercise->variants()->create([
            'name' => $variantName,
            'slug' => $this->uniqueVariantSlug($exercise->id, $variantName),
        ]);
    }

    private function uniqueExerciseSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'exercice';
        $slug = $base;
        $i = 1;
        while (Exercise::query()->where('coach_id', $this->coach->id)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function uniqueVariantSlug(int $exerciseId, string $name): string
    {
        $base = Str::slug($name) ?: 'variante';
        $slug = $base;
        $i = 1;
        while (ExerciseVariant::query()->where('exercise_id', $exerciseId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function normalizeLift(?string $lift): ?string
    {
        if ($lift === null || trim($lift) === '') {
            return null;
        }

        $value = mb_strtolower(trim($lift));
        $map = [
            'squat' => Exercise::LIFT_SQUAT,
            'sq' => Exercise::LIFT_SQUAT,
            'bench' => Exercise::LIFT_BENCH,
            'dc' => Exercise::LIFT_BENCH,
            'bp' => Exercise::LIFT_BENCH,
            'deadlift' => Exercise::LIFT_DEADLIFT,
            'sdt' => Exercise::LIFT_DEADLIFT,
            'dl' => Exercise::LIFT_DEADLIFT,
            'general' => Exercise::LIFT_GENERAL,
            'accessory' => Exercise::LIFT_GENERAL,
        ];

        return $map[$value] ?? (in_array($value, [
            Exercise::LIFT_SQUAT,
            Exercise::LIFT_BENCH,
            Exercise::LIFT_DEADLIFT,
            Exercise::LIFT_GENERAL,
        ], true) ? $value : null);
    }

    private function inferLift(string $variant, string $parent): string
    {
        $hay = mb_strtolower($variant.' '.$parent);
        if (str_contains($hay, 'squat') || str_contains($hay, 'leg press') || str_contains($hay, 'fente')) {
            return Exercise::LIFT_SQUAT;
        }
        if (preg_match('/\b(bench|dc|développé|developpe|ohp|presse)\b/u', $hay)) {
            return Exercise::LIFT_BENCH;
        }
        if (preg_match('/\b(deadlift|sdt|soulevé|souleve|rdl|row|tirage)\b/u', $hay)) {
            return Exercise::LIFT_DEADLIFT;
        }

        return Exercise::LIFT_GENERAL;
    }

    private function normalizeCategory(?string $category, string $lift, string $variantName): string
    {
        if ($category === Exercise::CATEGORY_MAIN_LIFT || $category === Exercise::CATEGORY_ACCESSORY) {
            return $category;
        }

        $hay = mb_strtolower($variantName);
        if (preg_match('/\b(squat|bench|dc|deadlift|sdt)\b/u', $hay)
            && in_array($lift, [Exercise::LIFT_SQUAT, Exercise::LIFT_BENCH, Exercise::LIFT_DEADLIFT], true)) {
            return Exercise::CATEGORY_MAIN_LIFT;
        }

        return Exercise::CATEGORY_ACCESSORY;
    }

    private function defaultParentName(string $lift, string $category, string $variantName): string
    {
        if ($category === Exercise::CATEGORY_MAIN_LIFT) {
            return match ($lift) {
                Exercise::LIFT_SQUAT => 'Squat',
                Exercise::LIFT_BENCH => 'Bench press',
                Exercise::LIFT_DEADLIFT => 'Deadlift',
                default => $variantName,
            };
        }

        return $variantName;
    }
}
