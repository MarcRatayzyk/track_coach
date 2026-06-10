<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesProgramSessionDay;
use Illuminate\Foundation\Http\FormRequest;

class StoreProgramSessionRequest extends FormRequest
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
        return array_merge([
            'week_number' => ['required', 'integer', 'min:1'],
            'weekday' => ['required', 'integer', 'min:1', 'max:7'],
        ], $this->programSessionDayRules());
    }
}
