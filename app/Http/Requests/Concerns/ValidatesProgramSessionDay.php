<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

trait ValidatesProgramSessionDay
{
    protected function prepareProgramSessionDecimalInputs(): void
    {
        $this->replace($this->normalizeProgramSessionDecimalInput($this->all()));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function normalizeProgramSessionDecimalInput(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalizeProgramSessionDecimalInput($value);
                continue;
            }

            if (! in_array($key, ['load', 'load_percent', 'rpe'], true) || ! is_string($value)) {
                continue;
            }

            $normalized = str_replace(',', '.', trim($value));
            $data[$key] = $normalized === '' ? null : $normalized;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function programSessionDayRules(): array
    {
        $exerciseLine = [
            'exercise_variant_id' => ['nullable', 'integer', 'exists:exercise_variants,id'],
            'exercise_name' => ['required', 'string', 'max:255'],
            'sets' => ['required', 'integer', 'min:1', 'max:10'],
            'reps' => ['required', 'integer', 'min:1', 'max:20'],
            'load' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'load_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rpe' => ['nullable', 'numeric', 'between:1,10'],
            'rest_seconds' => ['nullable', 'integer', 'min:0', 'max:900'],
        ];

        $blockRules = [
            'lift' => ['required', Rule::in(['squat', 'bench', 'deadlift'])],
            'topset' => ['nullable', 'array'],
            'topset.exercise_variant_id' => $exerciseLine['exercise_variant_id'],
            'topset.exercise_name' => ['required_with:blocks.*.topset', 'string', 'max:255'],
            'topset.sets' => ['required_with:blocks.*.topset', 'integer', 'min:1', 'max:10'],
            'topset.reps' => ['required_with:blocks.*.topset', 'integer', 'min:1', 'max:20'],
            'topset.load' => $exerciseLine['load'],
            'topset.load_percent' => $exerciseLine['load_percent'],
            'topset.rpe' => $exerciseLine['rpe'],
            'backoff' => ['nullable', 'array'],
            'backoff.exercise_variant_id' => $exerciseLine['exercise_variant_id'],
            'backoff.exercise_name' => ['required_with:blocks.*.backoff', 'string', 'max:255'],
            'backoff.sets' => ['required_with:blocks.*.backoff', 'integer', 'min:1', 'max:10'],
            'backoff.reps' => ['required_with:blocks.*.backoff', 'integer', 'min:1', 'max:20'],
            'backoff.load' => $exerciseLine['load'],
            'backoff.load_percent' => $exerciseLine['load_percent'],
            'backoff.rpe' => $exerciseLine['rpe'],
            'accessories' => ['nullable', 'array'],
            'accessories.*.exercise_variant_id' => $exerciseLine['exercise_variant_id'],
            'accessories.*.exercise_name' => ['required', 'string', 'max:255'],
            'accessories.*.sets' => $exerciseLine['sets'],
            'accessories.*.reps' => $exerciseLine['reps'],
            'accessories.*.load' => $exerciseLine['load'],
            'accessories.*.load_percent' => $exerciseLine['load_percent'],
            'accessories.*.rpe' => $exerciseLine['rpe'],
        ];

        $rules = [
            'blocks' => ['present', 'array'],
            'items' => ['sometimes', 'array'],
            'items.*.section' => ['required', Rule::in([
                'topset',
                'backoff',
                'accessory',
            ])],
            'items.*.exercise_variant_id' => $exerciseLine['exercise_variant_id'],
            'items.*.exercise_name' => ['required', 'string', 'max:255'],
            'items.*.sets' => $exerciseLine['sets'],
            'items.*.reps' => $exerciseLine['reps'],
            'items.*.load' => $exerciseLine['load'],
            'items.*.load_percent' => $exerciseLine['load_percent'],
            'items.*.rpe' => $exerciseLine['rpe'],
            'items.*.lift' => ['nullable', Rule::in(['squat', 'bench', 'deadlift'])],
            'items.*.rest_seconds' => $exerciseLine['rest_seconds'],
            'main_lift' => ['nullable', Rule::in(['squat', 'bench', 'deadlift'])],
            'session_label' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];

        foreach ($blockRules as $key => $rule) {
            $rules["blocks.*.{$key}"] = $rule;
        }

        return $rules;
    }
}
