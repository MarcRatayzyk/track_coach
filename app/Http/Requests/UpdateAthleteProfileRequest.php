<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAthleteProfileFields;
use App\Models\AthleteProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAthleteProfileRequest extends FormRequest
{
    use ValidatesAthleteProfileFields;

    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return array_merge($this->athleteProfileFieldRules(), [
            'feedback_frequency' => [
                'nullable',
                'string',
                Rule::in([AthleteProfile::FREQUENCY_DAILY, AthleteProfile::FREQUENCY_WEEKLY]),
            ],
        ]);
    }
}
