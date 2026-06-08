<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $exerciseLine = [
            'exercise_variant_id' => ['nullable', 'integer', 'exists:exercise_variants,id'],
            'exercise_name' => ['required', 'string', 'max:255'],
            'sets' => ['required', 'integer', 'min:1', 'max:10'],
            'reps' => ['required', 'integer', 'min:1', 'max:20'],
            'load' => ['nullable', 'integer', 'min:0', 'max:999'],
            'rpe' => ['nullable', 'numeric', 'between:1,10'],
        ];

        return [
            'name' => ['required', 'string', 'max:255'],
            'goal' => ['nullable', 'string', 'max:255'],
            'level' => ['required', 'string', 'max:50'],
            'weeks' => ['required', 'array', 'min:1'],
            'weeks.*.week_number' => ['required', 'integer', 'min:1'],
            'weeks.*.block_type' => ['required', Rule::in(['volume', 'intensification', 'peaking'])],
            'weeks.*.days' => ['required', 'array', 'min:1'],
            'weeks.*.days.*.day_number' => ['required', 'integer', 'min:1', 'max:7'],
            'weeks.*.days.*.main_lift' => ['required', Rule::in(['squat', 'bench', 'deadlift'])],
            'weeks.*.days.*.topset' => ['nullable', 'array'],
            'weeks.*.days.*.topset.exercise_variant_id' => $exerciseLine['exercise_variant_id'],
            'weeks.*.days.*.topset.exercise_name' => ['required_with:weeks.*.days.*.topset', 'string', 'max:255'],
            'weeks.*.days.*.topset.sets' => ['required_with:weeks.*.days.*.topset', 'integer', 'min:1', 'max:10'],
            'weeks.*.days.*.topset.reps' => ['required_with:weeks.*.days.*.topset', 'integer', 'min:1', 'max:20'],
            'weeks.*.days.*.topset.load' => $exerciseLine['load'],
            'weeks.*.days.*.topset.rpe' => $exerciseLine['rpe'],
            'weeks.*.days.*.backoff' => ['nullable', 'array'],
            'weeks.*.days.*.backoff.exercise_variant_id' => $exerciseLine['exercise_variant_id'],
            'weeks.*.days.*.backoff.exercise_name' => ['required_with:weeks.*.days.*.backoff', 'string', 'max:255'],
            'weeks.*.days.*.backoff.sets' => ['required_with:weeks.*.days.*.backoff', 'integer', 'min:1', 'max:10'],
            'weeks.*.days.*.backoff.reps' => ['required_with:weeks.*.days.*.backoff', 'integer', 'min:1', 'max:20'],
            'weeks.*.days.*.backoff.load' => $exerciseLine['load'],
            'weeks.*.days.*.backoff.rpe' => $exerciseLine['rpe'],
            'weeks.*.days.*.accessories' => ['nullable', 'array'],
            'weeks.*.days.*.accessories.*.exercise_variant_id' => $exerciseLine['exercise_variant_id'],
            'weeks.*.days.*.accessories.*.exercise_name' => ['required', 'string', 'max:255'],
            'weeks.*.days.*.accessories.*.sets' => $exerciseLine['sets'],
            'weeks.*.days.*.accessories.*.reps' => $exerciseLine['reps'],
            'weeks.*.days.*.accessories.*.load' => $exerciseLine['load'],
            'weeks.*.days.*.accessories.*.rpe' => $exerciseLine['rpe'],
        ];
    }
}
