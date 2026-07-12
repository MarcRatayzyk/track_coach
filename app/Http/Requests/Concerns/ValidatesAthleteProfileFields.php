<?php

namespace App\Http\Requests\Concerns;

use App\Support\IpfWeightCategorySupport;
use Illuminate\Validation\Rule;

trait ValidatesAthleteProfileFields
{
    /**
     * @return array<string, mixed>
     */
    protected function athleteProfileFieldRules(): array
    {
        return [
            'birth_date' => ['nullable', 'date', 'before:today'],
            'height_cm' => ['nullable', 'integer', 'min:100', 'max:250'],
            'sex' => ['nullable', 'string', Rule::in([IpfWeightCategorySupport::SEX_MALE, IpfWeightCategorySupport::SEX_FEMALE])],
            'weight_category' => ['nullable', 'string', Rule::in(IpfWeightCategorySupport::allCategories())],
            'level' => ['nullable', 'string', Rule::in(IpfWeightCategorySupport::LEVELS)],
            'injuries_notes' => ['nullable', 'string', 'max:2000'],
            'bio' => ['nullable', 'string', 'max:5000'],
            'profession' => ['nullable', 'string', 'max:120'],
            'years_training' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function athleteProfileAttributes(array $validated): array
    {
        return [
            'birth_date' => $validated['birth_date'] ?? null,
            'height_cm' => $validated['height_cm'] ?? null,
            'sex' => $validated['sex'] ?? null ?: null,
            'weight_category' => $validated['weight_category'] ?? null ?: null,
            'level' => $validated['level'] ?? null ?: null,
            'injuries_notes' => $validated['injuries_notes'] ?? null ?: null,
            'bio' => $validated['bio'] ?? null ?: null,
            'profession' => $validated['profession'] ?? null ?: null,
            'years_training' => $validated['years_training'] ?? null,
        ];
    }
}
