<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveCoachStatsDashboardItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', 'string', Rule::in(['up', 'down'])],
            'assignment' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
