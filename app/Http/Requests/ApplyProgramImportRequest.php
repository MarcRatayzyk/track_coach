<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesProgramSessionDay;
use Illuminate\Foundation\Http\FormRequest;

class ApplyProgramImportRequest extends FormRequest
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
            'operations' => ['required', 'array', 'min:1', 'max:84'],
            'operations.*.week_number' => ['required', 'integer', 'min:1'],
            'operations.*.weekday' => ['required', 'integer', 'min:1', 'max:7'],
            'exercises_to_create' => ['nullable', 'array'],
            'exercises_to_create.*.parent_name' => ['required_with:exercises_to_create', 'string', 'max:120'],
            'exercises_to_create.*.variant_name' => ['required_with:exercises_to_create', 'string', 'max:120'],
            'exercises_to_create.*.lift' => ['nullable', 'string', 'max:30'],
            'exercises_to_create.*.category' => ['nullable', 'string', 'max:30'],
            'builder_tab' => ['nullable', 'string'],
        ];

        foreach ($this->programSessionDayRules() as $key => $rule) {
            $rules["operations.*.{$key}"] = $rule;
        }

        // Soften item rules for import extras
        $rules['operations.*.items.*.will_create'] = ['sometimes', 'boolean'];
        $rules['operations.*.items.*.parent_name'] = ['nullable', 'string', 'max:120'];
        $rules['operations.*.items.*.variant_name'] = ['nullable', 'string', 'max:120'];
        $rules['operations.*.items.*.category'] = ['nullable', 'string', 'max:30'];

        return $rules;
    }
}
