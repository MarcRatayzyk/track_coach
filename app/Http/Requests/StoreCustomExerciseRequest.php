<?php

namespace App\Http\Requests;

use App\Models\Exercise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canProgramTraining() === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'lift' => ['required', Rule::in([
                Exercise::LIFT_SQUAT,
                Exercise::LIFT_BENCH,
                Exercise::LIFT_DEADLIFT,
                Exercise::LIFT_GENERAL,
            ])],
            'category' => ['required', Rule::in([
                Exercise::CATEGORY_MAIN_LIFT,
                Exercise::CATEGORY_ACCESSORY,
            ])],
            'equipment' => ['nullable', Rule::in([
                'barbell',
                'dumbbell',
                'machine',
                'cable',
                'bodyweight',
                'other',
            ])],
            'movement_pattern' => ['nullable', 'string', 'max:80'],
            'parent_exercise_id' => [
                Rule::requiredIf(fn () => $this->input('category') === Exercise::CATEGORY_ACCESSORY),
                'nullable',
                'integer',
                Rule::exists('exercises', 'id')->where(fn ($query) => $query
                    ->where('category', Exercise::CATEGORY_ACCESSORY)
                    ->where('is_custom', false)
                    ->whereNull('coach_id')),
            ],
        ];
    }
}
