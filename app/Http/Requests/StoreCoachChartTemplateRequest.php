<?php

namespace App\Http\Requests;

use App\Support\ChartTemplateSupport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoachChartTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    public function rules(): array
    {
        return $this->chartTemplateRules();
    }

    /**
     * @return array<string, mixed>
     */
    protected function chartTemplateRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'chartType' => ['required', 'string', Rule::in(ChartTemplateSupport::allowedChartTypes())],
            'metric' => ['required', 'string', Rule::in(ChartTemplateSupport::allowedMetrics())],
            'groupBy' => ['required', 'string', Rule::in(ChartTemplateSupport::allowedGroupBy())],
            'series' => ['required', 'array'],
            'series.*' => ['string', Rule::in(ChartTemplateSupport::allowedSeries())],
            'stacked' => ['sometimes', 'boolean'],
            'filters' => ['sometimes', 'array'],
            'filters.mainLift' => ['sometimes', 'string', Rule::in(ChartTemplateSupport::allowedMainLiftFilters())],
            'filters.repFormat' => ['sometimes', 'string', Rule::in(ChartTemplateSupport::allowedRepFormats())],
            'filters.section' => ['sometimes', 'string', Rule::in(ChartTemplateSupport::allowedSections())],
            'filters.weekFrom' => ['nullable', 'integer', 'min:1'],
            'filters.weekTo' => ['nullable', 'integer', 'min:1'],
            'filters.exerciseName' => ['nullable', 'string', 'max:255'],
            'add_to_dashboard' => ['sometimes', 'boolean'],
            'assignment' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
