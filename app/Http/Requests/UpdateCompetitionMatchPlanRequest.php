<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesMatchPlanData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompetitionMatchPlanRequest extends FormRequest
{
    use ValidatesMatchPlanData;

    public function authorize(): bool
    {
        $athlete = $this->route('athlete');

        return $this->user()?->id === $athlete?->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->matchPlanDataRules();
    }

    /**
     * @return array<string, mixed>
     */
    public function matchPlanPayload(): array
    {
        return $this->competitionPayload();
    }
}
