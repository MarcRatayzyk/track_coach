<?php

namespace App\Http\Requests;

use App\Models\AthleteProfile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCoachAthleteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'coach';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:120'],
            'last_name' => ['required', 'string', 'max:120'],
            'feedback_frequency' => [
                'required',
                'string',
                Rule::in([AthleteProfile::FREQUENCY_DAILY, AthleteProfile::FREQUENCY_WEEKLY]),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'feedback_frequency.required' => 'Choisis le type de coaching.',
            'feedback_frequency.in' => 'Choisis un type de coaching valide.',
        ];
    }
}
