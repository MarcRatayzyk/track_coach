<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesAthleteProfileFields;
use App\Models\AthleteProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnAthleteProfileRequest extends FormRequest
{
    use ValidatesAthleteProfileFields;

    public function authorize(): bool
    {
        $athlete = $this->route('athlete');

        return $this->user()?->id === $athlete?->id
            && $athlete?->role === 'athlete';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->athleteProfileFieldRules();
    }
}
