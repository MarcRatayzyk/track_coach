<?php

namespace App\Http\Requests;

use App\Support\DayTableLayoutSupport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDayTableLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'columns' => ['required', 'array'],
            'columns.*' => ['string', Rule::in(DayTableLayoutSupport::allowedColumnIds())],
            'exercise_mode' => ['required', 'string', Rule::in(DayTableLayoutSupport::allowedExerciseModes())],
            'load_mode' => ['required', 'string', Rule::in(DayTableLayoutSupport::allowedLoadModes())],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $columns = $this->input('columns', []);

            if (! is_array($columns)) {
                return;
            }

            $hasPrescription = collect($columns)->contains(fn ($column) => in_array($column, ['sets', 'reps', 'load'], true));

            if (! $hasPrescription) {
                $validator->errors()->add('columns', 'Active au moins une colonne de prescription (séries, reps ou charge).');
            }
        });
    }
}
