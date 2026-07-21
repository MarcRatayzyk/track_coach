<?php

namespace App\Http\Requests;

use App\Support\ReadinessFormSupport;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAthleteReadinessFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $athlete = $this->route('athlete');

        return $this->user()?->can('updateAthleteData', $athlete) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ReadinessFormSupport::fieldsValidationRules();
    }
}
