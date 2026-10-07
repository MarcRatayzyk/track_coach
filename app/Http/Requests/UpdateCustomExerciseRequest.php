<?php

namespace App\Http\Requests;

use App\Models\Exercise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $exercise = $this->route('exercise');

        return $exercise instanceof Exercise
            && $this->user()?->can('update', $exercise);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'lift' => ['sometimes', 'required', Rule::in([
                Exercise::LIFT_SQUAT,
                Exercise::LIFT_BENCH,
                Exercise::LIFT_DEADLIFT,
                Exercise::LIFT_GENERAL,
            ])],
            'category' => ['sometimes', 'required', Rule::in([
                Exercise::CATEGORY_MAIN_LIFT,
                Exercise::CATEGORY_ACCESSORY,
            ])],
            'equipment' => ['sometimes', 'nullable', Rule::in([
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
