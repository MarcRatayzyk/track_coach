<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoachStatsDashboardItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        return [
            'template_id' => ['required', 'integer', 'exists:coach_chart_templates,id'],
            'assignment' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
