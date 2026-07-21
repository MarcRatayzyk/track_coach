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
            $items = $this->input('items', []);
            $notes = trim((string) $this->input('notes', ''));

            if (! TrainingSessionSupport::hasExerciseContent($items) && $notes === '') {
                $validator->errors()->add(
                    'items',
                    'Ajoute au moins un exercice ou une note pour enregistrer la séance.',
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.*.load.numeric' => 'La charge doit être un nombre (ex. 140 ou 138,5).',
            'items.*.sets.integer' => 'Le nombre de séries doit être un entier.',
            'items.*.reps.integer' => 'Le nombre de reps doit être un entier.',
        ];
    }
}
