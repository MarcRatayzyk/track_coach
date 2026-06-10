<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesProgramSessionDay;
use Illuminate\Foundation\Http\FormRequest;

class BulkUpsertProgramSessionsRequest extends FormRequest
{
    use ValidatesProgramSessionDay;

    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    protected function prepareForValidation(): void
    {
        $this->prepareProgramSessionDecimalInputs();
    }

    public function rules(): array
    {
        $rules = [
            'operations' => ['required', 'array', 'min:1', 'max:56'],
            'operations.*.week_number' => ['required', 'integer', 'min:1'],
            'operations.*.weekday' => ['required', 'integer', 'min:1', 'max:7'],
        ];

        foreach ($this->programSessionDayRules() as $key => $rule) {
            $rules["operations.*.{$key}"] = $rule;
        }

        return $rules;
    }
}
