<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesMatchPlanData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompetitionRequest extends FormRequest
{
    use ValidatesMatchPlanData;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge([
            'name' => ['required', 'string', 'max:255'],
            'competition_date' => ['required', 'date'],
            'goal' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ], $this->matchPlanDataRules());
    }
}
