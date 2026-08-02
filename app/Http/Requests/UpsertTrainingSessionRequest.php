<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesProgramSessionDay;
use App\Support\TrainingSessionSupport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpsertTrainingSessionRequest extends FormRequest
{
    use ValidatesProgramSessionDay;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'session_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'items.*.athlete_note' => ['nullable', 'string', 'max:1000'],
        ], $this->programSessionDayRules());
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);

        if (! is_array($items)) {
            return;
        }

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                continue;
            }

            if (array_key_exists('exercise_variant_id', $item) && $item['exercise_variant_id'] === '') {
                $items[$index]['exercise_variant_id'] = null;
            }
        }

        $this->merge(['items' => $items]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateSetSchemes($validator);

            $items = $this->input('items', []);
            $notes = trim((string) $this->input('notes', ''));

            if (! TrainingSessionSupport::hasExerciseContent($items) && $notes === '') {
                $validator->errors()->add(
                    'items',
                    __('messages.validation.session_needs_content'),
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.*.load.numeric' => __('messages.validation.load_numeric'),
            'items.*.sets.integer' => __('messages.validation.sets_integer'),
            'items.*.reps.integer' => __('messages.validation.reps_integer'),
        ];
    }
}
