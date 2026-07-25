<?php

namespace App\Http\Requests\Concerns;

use App\Models\ProgramDayExercise;
use App\Support\SetSchemeSupport;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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
            'set_scheme' => ['nullable', Rule::in(ProgramDayExercise::SCHEMES)],
            'scheme_config' => ['nullable', 'array'],
            'scheme_config.steps' => ['nullable', 'array', 'min:1', 'max:8'],
            'scheme_config.steps.*.reps' => ['nullable', 'integer', 'min:1', 'max:20'],
            'scheme_config.steps.*.load' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'scheme_config.steps.*.load_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'scheme_config.steps.*.rpe' => ['nullable', 'numeric', 'between:1,10'],
            'scheme_config.reps' => ['nullable', 'integer', 'min:1', 'max:200'],
            'scheme_config.duration_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'sets' => ['required', 'integer', 'min:1', 'max:10'],
            'reps' => ['required', 'integer', 'min:1', 'max:200'],
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
            'topset.set_scheme' => $exerciseLine['set_scheme'],
            'topset.scheme_config' => $exerciseLine['scheme_config'],
            'topset.scheme_config.steps' => $exerciseLine['scheme_config.steps'],
            'topset.scheme_config.steps.*.reps' => $exerciseLine['scheme_config.steps.*.reps'],
            'topset.scheme_config.steps.*.load' => $exerciseLine['scheme_config.steps.*.load'],
            'topset.scheme_config.steps.*.load_percent' => $exerciseLine['scheme_config.steps.*.load_percent'],
            'topset.scheme_config.steps.*.rpe' => $exerciseLine['scheme_config.steps.*.rpe'],
            'topset.scheme_config.reps' => $exerciseLine['scheme_config.reps'],
            'topset.scheme_config.duration_minutes' => $exerciseLine['scheme_config.duration_minutes'],
            'topset.sets' => ['required_with:blocks.*.topset', 'integer', 'min:1', 'max:10'],
            'topset.reps' => ['required_with:blocks.*.topset', 'integer', 'min:1', 'max:200'],
            'topset.load' => $exerciseLine['load'],
            'topset.load_percent' => $exerciseLine['load_percent'],
            'topset.rpe' => $exerciseLine['rpe'],
            'backoff' => ['nullable', 'array'],
            'backoff.exercise_variant_id' => $exerciseLine['exercise_variant_id'],
            'backoff.exercise_name' => ['required_with:blocks.*.backoff', 'string', 'max:255'],
            'backoff.set_scheme' => $exerciseLine['set_scheme'],
            'backoff.scheme_config' => $exerciseLine['scheme_config'],
            'backoff.scheme_config.steps' => $exerciseLine['scheme_config.steps'],
            'backoff.scheme_config.steps.*.reps' => $exerciseLine['scheme_config.steps.*.reps'],
            'backoff.scheme_config.steps.*.load' => $exerciseLine['scheme_config.steps.*.load'],
            'backoff.scheme_config.steps.*.load_percent' => $exerciseLine['scheme_config.steps.*.load_percent'],
            'backoff.scheme_config.steps.*.rpe' => $exerciseLine['scheme_config.steps.*.rpe'],
            'backoff.scheme_config.reps' => $exerciseLine['scheme_config.reps'],
            'backoff.scheme_config.duration_minutes' => $exerciseLine['scheme_config.duration_minutes'],
            'backoff.sets' => ['required_with:blocks.*.backoff', 'integer', 'min:1', 'max:10'],
            'backoff.reps' => ['required_with:blocks.*.backoff', 'integer', 'min:1', 'max:200'],
            'backoff.load' => $exerciseLine['load'],
            'backoff.load_percent' => $exerciseLine['load_percent'],
            'backoff.rpe' => $exerciseLine['rpe'],
            'accessories' => ['nullable', 'array'],
            'accessories.*.exercise_variant_id' => $exerciseLine['exercise_variant_id'],
            'accessories.*.exercise_name' => ['required', 'string', 'max:255'],
            'accessories.*.set_scheme' => $exerciseLine['set_scheme'],
            'accessories.*.scheme_config' => $exerciseLine['scheme_config'],
            'accessories.*.scheme_config.steps' => $exerciseLine['scheme_config.steps'],
            'accessories.*.scheme_config.steps.*.reps' => $exerciseLine['scheme_config.steps.*.reps'],
            'accessories.*.scheme_config.steps.*.load' => $exerciseLine['scheme_config.steps.*.load'],
            'accessories.*.scheme_config.steps.*.load_percent' => $exerciseLine['scheme_config.steps.*.load_percent'],
            'accessories.*.scheme_config.steps.*.rpe' => $exerciseLine['scheme_config.steps.*.rpe'],
            'accessories.*.scheme_config.reps' => $exerciseLine['scheme_config.reps'],
            'accessories.*.scheme_config.duration_minutes' => $exerciseLine['scheme_config.duration_minutes'],
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
                'warmup',
            ])],
            'items.*.exercise_variant_id' => $exerciseLine['exercise_variant_id'],
            'items.*.exercise_name' => ['required', 'string', 'max:255'],
            'items.*.set_scheme' => $exerciseLine['set_scheme'],
            'items.*.scheme_config' => $exerciseLine['scheme_config'],
            'items.*.scheme_config.steps' => $exerciseLine['scheme_config.steps'],
            'items.*.scheme_config.steps.*.reps' => $exerciseLine['scheme_config.steps.*.reps'],
            'items.*.scheme_config.steps.*.load' => $exerciseLine['scheme_config.steps.*.load'],
            'items.*.scheme_config.steps.*.load_percent' => $exerciseLine['scheme_config.steps.*.load_percent'],
            'items.*.scheme_config.steps.*.rpe' => $exerciseLine['scheme_config.steps.*.rpe'],
            'items.*.scheme_config.reps' => $exerciseLine['scheme_config.reps'],
            'items.*.scheme_config.duration_minutes' => $exerciseLine['scheme_config.duration_minutes'],
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
            'warmup_override' => ['sometimes', 'boolean'],
            'warmup_notes' => ['nullable', 'string', 'max:5000'],
        ];

        foreach ($blockRules as $key => $rule) {
            $rules["blocks.*.{$key}"] = $rule;
        }

        return $rules;
    }

    protected function validateSetSchemes(Validator $validator): void
    {
        $items = $this->input('items', []);
        if (is_array($items)) {
            foreach ($items as $index => $item) {
                if (! is_array($item)) {
                    continue;
                }
                $this->assertSetSchemeValid($validator, "items.{$index}", $item);
            }
        }

        $blocks = $this->input('blocks', []);
        if (! is_array($blocks)) {
            return;
        }

        foreach ($blocks as $blockIndex => $block) {
            if (! is_array($block)) {
                continue;
            }

            foreach (['topset', 'backoff'] as $key) {
                if (empty($block[$key]) || ! is_array($block[$key])) {
                    continue;
                }
                $this->assertSetSchemeValid($validator, "blocks.{$blockIndex}.{$key}", $block[$key]);
            }

            foreach ($block['accessories'] ?? [] as $accIndex => $accessory) {
                if (! is_array($accessory)) {
                    continue;
                }
                $this->assertSetSchemeValid(
                    $validator,
                    "blocks.{$blockIndex}.accessories.{$accIndex}",
                    $accessory,
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $line
     */
    protected function assertSetSchemeValid(Validator $validator, string $prefix, array $line): void
    {
        $scheme = SetSchemeSupport::resolveScheme($line['set_scheme'] ?? null);

        if ($scheme === ProgramDayExercise::SCHEME_RAMP) {
            $steps = SetSchemeSupport::normalizeRampSteps($line['scheme_config']['steps'] ?? []);
            if ($steps === []) {
                $validator->errors()->add(
                    "{$prefix}.scheme_config.steps",
                    'Ajoute au moins un palier ramp valide (reps + charge).',
                );
            }
        }

        if ($scheme === ProgramDayExercise::SCHEME_CLUSTER) {
            $reps = $line['scheme_config']['reps'] ?? $line['reps'] ?? null;
            $minutes = $line['scheme_config']['duration_minutes'] ?? null;
            if (! is_numeric($reps) || (int) $reps < 1) {
                $validator->errors()->add(
                    "{$prefix}.scheme_config.reps",
                    'Indique le nombre de reps du cluster.',
                );
            }
            if (! is_numeric($minutes) || (int) $minutes < 1) {
                $validator->errors()->add(
                    "{$prefix}.scheme_config.duration_minutes",
                    'Indique la durée du cluster en minutes.',
                );
            }
        }
    }
}
