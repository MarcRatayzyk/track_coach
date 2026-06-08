<?php

namespace App\Http\Requests;

class UpdateCoachChartTemplateRequest extends StoreCoachChartTemplateRequest
{
    public function rules(): array
    {
        return $this->chartTemplateRules();
    }
}
