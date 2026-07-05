<?php

namespace App\Http\Requests;

use App\Models\Exercise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
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
            'equipment' => ['required', Rule::in([
                'barbell',
                'dumbbell',
                'machine',
                'cable',
                'bodyweight',
                'other',
            ])],
            'movement_pattern' => ['nullable', 'string', 'max:80'],
        ];
    }
}
