<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignProgramTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('date_end') === '' || $this->input('date_end') === null) {
            $this->merge(['date_end' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'athlete_id' => ['required', 'integer', 'exists:users,id'],
            'date_start' => ['required', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
        ];
    }
}
