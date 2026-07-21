<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgramBlockWarmupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        return [
            'default_warmup_notes' => ['nullable', 'string', 'max:5000'],
            'default_warmup_items' => ['nullable', 'array'],
            'default_warmup_items.*.exercise_variant_id' => ['nullable', 'integer', 'exists:exercise_variants,id'],
            'default_warmup_items.*.exercise_name' => ['required', 'string', 'max:255'],
            'default_warmup_items.*.lift' => ['nullable', Rule::in(['squat', 'bench', 'deadlift'])],
            'default_warmup_items.*.sets' => ['nullable', 'integer', 'min:1', 'max:10'],
            'default_warmup_items.*.reps' => ['nullable', 'integer', 'min:1', 'max:20'],
            'default_warmup_items.*.load' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'default_warmup_items.*.load_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'default_warmup_items.*.rpe' => ['nullable', 'numeric', 'between:1,10'],
            'default_warmup_items.*.rest_seconds' => ['nullable', 'integer', 'min:0', 'max:900'],
        ];
    }
}
